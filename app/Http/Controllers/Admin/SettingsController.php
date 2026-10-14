<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Domain\Notification\Clients\VexaHostClient;
use App\Domain\Payment\Services\PaymentGatewayManager;
use App\Domain\Settings\SettingsRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        $activeGroup = $request->query('group', 'general');

        // Group registry items
        $groupedSettings = [];
        foreach ($registry as $item) {
            $group = $item['group'] ?? 'general';
            $key = $item['key'] ?? null;
            if ($key) {
                $item['current_value'] = $this->settings->get($key);
                $groupedSettings[$group][] = $item;
            }
        }

        $groups = [
            'general' => 'Umum & Operasional',
            'company' => 'Identitas Perusahaan',
            'booking' => 'Reservasi & SLA',
            'payment' => 'Gerbang Pembayaran',
            'whatsapp' => 'WhatsApp Gateway',
            'email' => 'Email SMTP',
            'finance' => 'Keuangan & Dokumen',
        ];

        return view('admin.settings.index', compact('groupedSettings', 'groups', 'activeGroup'));
    }

    public function update(Request $request)
    {
        $registry = config('destinara.settings', []);

        foreach ($registry as $item) {
            $key = $item['key'] ?? null;
            if (! $key) {
                continue;
            }

            $underscoreKey = str_replace('.', '_', $key);
            if ($request->has($underscoreKey)) {
                $this->settings->set($key, $request->input($underscoreKey));
            } elseif ($request->has($key)) {
                $this->settings->set($key, $request->input($key));
            }
        }

        AuditLogger::log('settings_updated', null, 'Memperbarui konfigurasi pengaturan sistem.');

        return back()->with('success', 'Pengaturan sistem berhasil disimpan dan diperbarui di cache.');
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
