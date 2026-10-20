<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Notification\Clients\VexaHostClient;
use App\Domain\Payment\Services\PaymentGatewayManager;
use App\Domain\Settings\SettingsRepository;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

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
                $val = $this->settings->get($key);
                if (($item['type'] ?? '') === 'secret') {
                    $item['has_value'] = ! empty($val);
                    $valStr = (string) $val;
                    $item['masked_suffix'] = ! empty($valStr) ? substr($valStr, -4) : '';
                    $item['current_value'] = '';
                } else {
                    $item['current_value'] = $val;
                }
                $groupedSettings[$group][] = $item;
                $availableGroups[$group] = $groupLabels[$group] ?? ucfirst($group);
            }
        }

        if (empty($availableGroups)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah pengaturan sistem.');
        }

        $activeGroup = $request->old('group', $request->query('group'));
        if (! $activeGroup || ! isset($availableGroups[$activeGroup])) {
            $activeGroup = array_key_first($availableGroups);
        }

        $banks = Bank::where('is_active', true)->orderBy('name')->get();

        return view('admin.settings.index', [
            'groupedSettings' => $groupedSettings,
            'groups' => $availableGroups,
            'activeGroup' => $activeGroup,
            'banks' => $banks,
        ]);
    }

    public function update(Request $request)
    {
        $registry = config('destinara.settings', []);
        $user = $request->user();
        $targetGroup = $request->input('group');

        if ($targetGroup) {
            $permission = "settings.{$targetGroup}";
            if ($user && ! $user->can($permission)) {
                abort(403, "Anda tidak memiliki izin untuk mengubah pengaturan grup '{$targetGroup}'.");
            }
        }

        // Handle missing checkboxes/repeaters when submitted empty
        if ($targetGroup === 'payment') {
            if (! $request->has('enabled_gateways')) {
                $request->merge(['enabled_gateways' => []]);
            }
            if (! $request->has('manual_transfer_accounts')) {
                $request->merge(['manual_transfer_accounts' => []]);
            }
        } elseif ($targetGroup === 'notification') {
            if (! $request->has('admin_alert_emails')) {
                $request->merge(['admin_alert_emails' => []]);
            }
            if (! $request->has('admin_alert_whatsapp')) {
                $request->merge(['admin_alert_whatsapp' => []]);
            }
        }

        $validationRules = [];
        $keysToUpdate = [];

        foreach ($registry as $item) {
            $key = $item['key'] ?? null;
            if (! $key) {
                continue;
            }

            $group = $item['group'] ?? 'general';
            if ($targetGroup && $group !== $targetGroup) {
                continue;
            }

            $underscoreKey = str_replace('.', '_', $key);
            $hasField = $request->has($underscoreKey) || $request->has($key);
            $deleteSecretKey = 'delete_secret_'.$key;
            $hasDeleteSecret = $request->boolean($deleteSecretKey);

            if (($item['type'] ?? '') === 'secret') {
                $permission = $item['permission'] ?? ("settings.{$group}");
                if ($user && ! $user->can($permission)) {
                    abort(403, "Anda tidak memiliki izin untuk mengubah pengaturan grup '{$group}'.");
                }

                if ($hasDeleteSecret) {
                    $keysToUpdate[$key] = [
                        'input_key' => $inputKey ?? $key,
                        'type' => 'secret',
                        'forced_value' => '',
                    ];

                    continue;
                }

                $inputKey = $request->has($underscoreKey) ? $underscoreKey : $key;
                $rawSecret = $request->input($inputKey);
                if ($rawSecret === null || trim((string) $rawSecret) === '') {
                    // Empty input means retain existing secret value
                    continue;
                }

                if (! empty($item['rules'])) {
                    $validationRules[$inputKey] = $item['rules'];
                }
                $keysToUpdate[$key] = [
                    'input_key' => $inputKey,
                    'type' => 'secret',
                ];

                continue;
            }

            if ($hasField) {
                $permission = $item['permission'] ?? ("settings.{$group}");

                if ($user && ! $user->can($permission)) {
                    abort(403, "Anda tidak memiliki izin untuk mengubah pengaturan grup '{$group}'.");
                }

                $inputKey = $request->has($underscoreKey) ? $underscoreKey : $key;

                // Handle JSON fields pre-decoding or array sanitization before validation
                if (($item['type'] ?? '') === 'json') {
                    $raw = $request->input($inputKey);
                    if (is_string($raw)) {
                        $trimmed = trim($raw);
                        if ($trimmed === '' || $trimmed === '[]' || $trimmed === '{}') {
                            $request->merge([$inputKey => []]);
                        } else {
                            $decoded = json_decode($trimmed, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $request->merge([$inputKey => $decoded]);
                            } else {
                                return redirect()->route('admin.settings.index', ['group' => $targetGroup ?: 'general'])
                                    ->withInput()
                                    ->withErrors([$inputKey => "Format JSON pada pengaturan '{$item['label']}' tidak valid."]);
                            }
                        }
                    } elseif (is_array($raw)) {
                        if ($inputKey === 'manual_transfer_accounts') {
                            $cleaned = [];
                            foreach ($raw as $acc) {
                                if (is_array($acc) && (! empty($acc['bank']) || ! empty($acc['number']))) {
                                    $cleaned[] = [
                                        'bank' => trim((string) ($acc['bank'] ?? '')),
                                        'number' => trim((string) ($acc['number'] ?? '')),
                                        'holder' => trim((string) ($acc['holder'] ?? setting('company_name', config('app.name')))),
                                    ];
                                }
                            }
                            $request->merge([$inputKey => $cleaned]);
                        } elseif (in_array($inputKey, ['admin_alert_emails', 'admin_alert_whatsapp', 'enabled_gateways'])) {
                            $cleaned = array_values(array_filter(array_map('trim', (array) $raw)));
                            $request->merge([$inputKey => $cleaned]);
                        }
                    }
                }

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
            try {
                $request->validate($validationRules);
            } catch (ValidationException $e) {
                $redirectUrl = route('admin.settings.index', ['group' => $targetGroup ?: 'general']);
                throw $e->redirectTo($redirectUrl);
            }
        }

        foreach ($keysToUpdate as $key => $meta) {
            if (array_key_exists('forced_value', $meta)) {
                $this->settings->set($key, $meta['forced_value']);

                continue;
            }

            $val = $request->input($meta['input_key']);
            if ($meta['type'] === 'bool' || $meta['type'] === 'boolean') {
                $val = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            } elseif ($meta['type'] === 'int' || $meta['type'] === 'integer' || $meta['type'] === 'money') {
                $val = (int) $val;
            } elseif ($meta['type'] === 'float' || $meta['type'] === 'percent') {
                $val = (float) $val;
            } elseif ($meta['type'] === 'json' && is_string($val)) {
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $val = $decoded;
                }
            }
            $this->settings->set($key, $val);
        }

        AuditLogger::log('settings_updated', null, 'Memperbarui konfigurasi pengaturan sistem'.($targetGroup ? " grup '{$targetGroup}'" : '').'.');

        $redirectUrl = route('admin.settings.index');
        if ($targetGroup) {
            $redirectUrl .= '?group='.urlencode($targetGroup);
        }

        return redirect($redirectUrl)->with('success', 'Pengaturan sistem berhasil disimpan dan diperbarui di cache.');
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
