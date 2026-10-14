<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Notification\Clients\VexaHostClient;
use App\Domain\Payment\Services\PaymentGatewayManager;
use App\Domain\Settings\SettingsRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsRepository $settings
    ) {}

    /**
     * Halaman Pengaturan Sistem Dinamis dari Registry
     */
    public function index(Request $request)
    {
        $registry = config('destinara.settings', []);
        $user = $request->user();

        $groupLabels = [
            'general' => 'Umum & Operasional',
            'booking' => 'Reservasi & SLA',
            'finance' => 'Keuangan & Komisi',
            'payment' => 'Gerbang Pembayaran',
            'email' => 'Email SMTP',
            'whatsapp' => 'WhatsApp Gateway',
            'notification' => 'Notifikasi & Peringatan',
            'documents' => 'Format Dokumen',
        ];

        $groupedSettings = [];
        $availableGroups = [];

        foreach ($registry as $item) {
            $group = $item['group'] ?? 'general';
            $permission = $item['permission'] ?? ("settings.{$group}");

            if ($user && ! $user->can($permission)) {
                continue;
            }

            $key = $item['key'] ?? null;
            if ($key) {
                $item['current_value'] = $this->settings->get($key);
                $groupedSettings[$group][] = $item;
                $availableGroups[$group] = $groupLabels[$group] ?? ucfirst($group);
            }
        }

        if (empty($availableGroups)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah pengaturan sistem.');
        }

        $activeGroup = $request->query('group');
        if (! $activeGroup || ! isset($availableGroups[$activeGroup])) {
            $activeGroup = array_key_first($availableGroups);
        }

        return view('admin.settings.index', [
            'groupedSettings' => $groupedSettings,
            'groups' => $availableGroups,
            'activeGroup' => $activeGroup,
        ]);
    }

    public function update(Request $request)
    {
        $registry = config('destinara.settings', []);
        $user = $request->user();

        $validationRules = [];
        $keysToUpdate = [];

        foreach ($registry as $item) {
            $key = $item['key'] ?? null;
            if (! $key) {
                continue;
            }

            $underscoreKey = str_replace('.', '_', $key);
            $hasField = $request->has($underscoreKey) || $request->has($key);

            if ($hasField) {
                $group = $item['group'] ?? 'general';
                $permission = $item['permission'] ?? ("settings.{$group}");

                if ($user && ! $user->can($permission)) {
                    abort(403, "Anda tidak memiliki izin untuk mengubah pengaturan grup '{$group}'.");
                }

                $inputKey = $request->has($underscoreKey) ? $underscoreKey : $key;
                if (! empty($item['rules'])) {
                    $validationRules[$inputKey] = $item['rules'];
                }
                $keysToUpdate[$key] = [
                    'input_key' => $inputKey,
                    'type' => $item['type'] ?? 'string',
                ];
            }
        }

        if (! empty($validationRules)) {
            $request->validate($validationRules);
        }

        foreach ($keysToUpdate as $key => $meta) {
            $val = $request->input($meta['input_key']);
            if ($meta['type'] === 'bool') {
                $val = (bool) $val;
            } elseif ($meta['type'] === 'int') {
                $val = (int) $val;
            } elseif ($meta['type'] === 'json' && is_string($val)) {
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $val = $decoded;
                }
            }
            $this->settings->set($key, $val);
        }

        AuditLogger::log('settings_updated', null, 'Memperbarui konfigurasi pengaturan sistem.');

        return back()->with('success', 'Pengaturan sistem berhasil disimpan dan diperbarui di cache.');
    }

    /**
     * Delegator Tes Koneksi untuk Route /pengaturan/test/{target}
     */
    public function testConnection(
        Request $request,
        string $target,
        VexaHostClient $waClient,
        PaymentGatewayManager $gatewayManager
    ) {
        return match ($target) {
            'whatsapp' => $this->testWhatsApp($request, $waClient),
            'mail', 'email' => $this->testMail($request),
            'xendit' => $this->testXendit($gatewayManager),
            'midtrans' => $this->testMidtrans($gatewayManager),
            default => back()->with('error', "Target pengujian koneksi '{$target}' tidak dikenali."),
        };
    }

    /**
     * Tes Koneksi WhatsApp (VexaHost)
     */
    public function testWhatsApp(Request $request, VexaHostClient $client)
    {
        try {
            $health = $client->checkHealth();
            if ($health['success'] ?? false) {
                return back()->with('success', 'Koneksi ke VexaHost WhatsApp API BERHASIL! Status: '.($health['data']['status'] ?? 'Ready'));
            }

            return back()->with('error', 'Respon VexaHost: '.($health['error'] ?? 'Gagal menghubungi server gateway.'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Tes WhatsApp gagal: '.$e->getMessage());
        }
    }

    /**
     * Tes Koneksi Mailer (Brevo / SMTP)
     */
    public function testMail(Request $request)
    {
        $recipient = $request->input('test_email', Auth::user()->email ?? 'admin@destinara.id');

        try {
            Mail::raw('Ini adalah email uji coba dari konsol admin Destinara pada '.now()->toRfc850String(), function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('Tes Koneksi Email SMTP — Destinara');
            });

            return back()->with('success', "Email tes koneksi berhasil dikirim ke {$recipient}!");
        } catch (\Throwable $e) {
            return back()->with('error', 'Tes SMTP gagal: '.$e->getMessage());
        }
    }

    /**
     * Tes Koneksi Xendit
     */
    public function testXendit(PaymentGatewayManager $manager)
    {
        try {
            $xendit = $manager->driver('xendit');
            $success = $xendit->testConnection();

            if ($success) {
                return back()->with('success', 'Koneksi ke Xendit Invoice API v2 BERHASIL! Kredensial valid.');
            }

            return back()->with('error', 'Gagal memverifikasi saldo / kredensial Xendit. Periksa Secret Key.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Tes Xendit gagal: '.$e->getMessage());
        }
    }

    /**
     * Tes Koneksi Midtrans
     */
    public function testMidtrans(PaymentGatewayManager $manager)
    {
        try {
            $midtrans = $manager->driver('midtrans');
            $success = $midtrans->testConnection();

            if ($success) {
                return back()->with('success', 'Koneksi ke Midtrans Snap API BERHASIL! Server key valid.');
            }

            return back()->with('error', 'Gagal memverifikasi kredensial Midtrans Snap.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Tes Midtrans gagal: '.$e->getMessage());
        }
    }
}
