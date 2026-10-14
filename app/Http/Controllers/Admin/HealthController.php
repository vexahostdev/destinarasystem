<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Notification\Clients\VexaHostClient;
use App\Domain\Payment\Drivers\MidtransGateway;
use App\Domain\Payment\Drivers\XenditGateway;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthController extends Controller
{
    public function __construct(
        protected VexaHostClient $vexaHostClient,
        protected XenditGateway $xenditGateway,
        protected MidtransGateway $midtransGateway
    ) {}

    public function index()
    {
        $checks = [];

        // 1. Database
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            $tablesCount = count(DB::select('SHOW TABLES'));
            $latency = round((microtime(true) - $start) * 1000, 2);
            $checks['database'] = [
                'name' => 'Database Relasional (MySQL)',
                'status' => 'healthy',
                'latency' => "{$latency} ms",
                'detail' => "Terkoneksi. {$tablesCount} tabel terdeteksi.",
            ];
        } catch (Throwable $e) {
            $checks['database'] = [
                'name' => 'Database Relasional (MySQL)',
                'status' => 'unhealthy',
                'latency' => '-',
                'detail' => 'Koneksi gagal: '.$e->getMessage(),
            ];
        }

        // 2. Queue & Jobs
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            $checks['queue'] = [
                'name' => 'Background Queue Worker',
                'status' => $failedJobs > 0 ? 'warning' : 'healthy',
                'latency' => '-',
                'detail' => "{$pendingJobs} job antre, {$failedJobs} job gagal.",
            ];
        } catch (Throwable $e) {
            $checks['queue'] = [
                'name' => 'Background Queue Worker',
                'status' => 'warning',
                'latency' => '-',
                'detail' => 'Tabel queue tidak dapat diakses: '.$e->getMessage(),
            ];
        }

        // 3. Scheduler Heartbeat
        $heartbeat = Cache::get('scheduler:heartbeat');
        if ($heartbeat) {
            $diff = Carbon::parse($heartbeat)->diffForHumans();
            $checks['scheduler'] = [
                'name' => 'Task Scheduler',
                'status' => Carbon::parse($heartbeat)->diffInMinutes(now()) <= 10 ? 'healthy' : 'warning',
                'latency' => '-',
                'detail' => "Heartbeat terakhir: {$diff} ({$heartbeat})",
            ];
        } else {
            $checks['scheduler'] = [
                'name' => 'Task Scheduler',
                'status' => 'warning',
                'latency' => '-',
                'detail' => 'Belum ada detak heartbeat scheduler yang tercatat.',
            ];
        }

        // 4. WhatsApp VexaHost
        $start = microtime(true);
        $waHealth = $this->vexaHostClient->checkHealth();
        $latency = round((microtime(true) - $start) * 1000, 2);
        $checks['whatsapp'] = [
            'name' => 'VexaHost WhatsApp API',
            'status' => $waHealth['status'] === 'connected' ? 'healthy' : ($waHealth['status'] === 'degraded' ? 'warning' : 'unhealthy'),
            'latency' => "{$latency} ms",
            'detail' => $waHealth['status'] === 'connected' ? 'API VexaHost aktif & terhubung.' : ($waHealth['error'] ?? 'API merespons tidak normal.'),
        ];

        // 5. Payment Gateways
        $start = microtime(true);
        $xenditOk = $this->xenditGateway->testConnection();
        $latency = round((microtime(true) - $start) * 1000, 2);
        $checks['xendit'] = [
            'name' => 'Xendit Invoice v2 Gateway',
            'status' => $xenditOk ? 'healthy' : 'unhealthy',
            'latency' => "{$latency} ms",
            'detail' => $xenditOk ? 'Kredensial & koneksi Xendit valid.' : 'Gagal menghubungi Xendit API.',
        ];

        $start = microtime(true);
        $midtransOk = $this->midtransGateway->testConnection();
        $latency = round((microtime(true) - $start) * 1000, 2);
        $checks['midtrans'] = [
            'name' => 'Midtrans Snap Payment Gateway',
            'status' => $midtransOk ? 'healthy' : 'unhealthy',
            'latency' => "{$latency} ms",
            'detail' => $midtransOk ? 'Koneksi Midtrans terverifikasi.' : 'Gagal menghubungi Midtrans Snap API.',
        ];

        // 6. Storage & Disk
        try {
            $testFile = 'health_check_'.time().'.txt';
            Storage::disk('local')->put($testFile, 'OK');
            Storage::disk('local')->delete($testFile);
            $checks['storage'] = [
                'name' => 'Penyimpanan Dokumen & Storage',
                'status' => 'healthy',
                'latency' => '-',
                'detail' => 'Disk lokal dan direktori dokumen dapat ditulisi (writable).',
            ];
        } catch (Throwable $e) {
            $checks['storage'] = [
                'name' => 'Penyimpanan Dokumen & Storage',
                'status' => 'unhealthy',
                'latency' => '-',
                'detail' => 'Storage tidak dapat ditulisi: '.$e->getMessage(),
            ];
        }

        // 7. Backup Terakhir
        $backupPath = storage_path('app/backup');
        $lastBackupTime = '-';
        if (is_dir($backupPath)) {
            $files = glob($backupPath.'/*.zip');
            if (! empty($files)) {
                $latest = max(array_map('filemtime', $files));
                $lastBackupTime = Carbon::createFromTimestamp($latest)->diffForHumans();
            }
        }
        $checks['backup'] = [
            'name' => 'Backup Sistem & Database',
            'status' => $lastBackupTime !== '-' ? 'healthy' : 'info',
            'latency' => '-',
            'detail' => $lastBackupTime !== '-' ? "Cadangan terakhir: {$lastBackupTime}" : 'Belum ada arsip backup terdeteksi.',
        ];

        return view('admin.health.index', compact('checks'));
    }
}
