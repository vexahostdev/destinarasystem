<?php

namespace App\Console\Commands;

use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationLog;
use Illuminate\Console\Command;

class RetryFailedNotificationsCommand extends Command
{
    protected $signature = 'notifications:retry-failed';

    protected $description = 'Retry retryable failed notification logs up to maximum 3 attempts (SPEC §12)';

    public function handle(): int
    {
        $failedLogs = NotificationLog::where('status', NotificationStatus::FAILED)
            ->where('attempts', '<', 3)
            ->where(function ($q) {
                // Exclude known non-retryable errors
                $q->whereNull('error')
                    ->orWhere(function ($sub) {
                        $sub->where('error', 'not like', '%422%')
                            ->where('error', 'not like', '%tidak valid%')
                            ->where('error', 'not like', '%belum dikonfigurasi%');
                    });
            })
            ->get();

        $count = 0;
        foreach ($failedLogs as $log) {
            $log->status = NotificationStatus::QUEUED;
            $log->save();

            SendNotificationJob::dispatch($log->id);
            $count++;
        }

        $this->info("Berhasil mengirim ulang {$count} notifikasi yang gagal.");

        return Command::SUCCESS;
    }
}
