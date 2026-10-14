<?php

namespace App\Jobs;

use App\Domain\Notification\Clients\VexaHostClient;
use App\Domain\Notification\Exceptions\NonRetryableNotificationException;
use App\Domain\Notification\Exceptions\RateLimitedNotificationException;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Mail\TemplatedMail;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $notificationLogId
    ) {
        $this->onQueue('notifications');
    }

    public function handle(VexaHostClient $waClient): void
    {
        $log = NotificationLog::find($this->notificationLogId);
        if (! $log) {
            return;
        }

        $log->attempts++;

        try {
            match ($log->channel) {
                NotificationChannel::MAIL => $this->sendMail($log),
                NotificationChannel::WHATSAPP => $this->sendWhatsApp($log, $waClient),
                NotificationChannel::DATABASE => $this->sendDatabaseNotification($log),
            };

            $log->status = NotificationStatus::SENT;
            $log->sent_at = now();
            $log->save();
        } catch (RateLimitedNotificationException $e) {
            $log->error = $e->getMessage();
            $log->save();
            $this->release($e->retryAfter);
        } catch (NonRetryableNotificationException $e) {
            $log->status = NotificationStatus::FAILED;
            $log->error = $e->getMessage();
            $log->save();
        } catch (\Throwable $e) {
            $log->error = $e->getMessage();
            if ($this->attempts() >= $this->tries) {
                $log->status = NotificationStatus::FAILED;
            }
            $log->save();
            throw $e;
        }
    }

    protected function sendMail(NotificationLog $log): void
    {
        if (empty($log->recipient) || ! filter_var($log->recipient, FILTER_VALIDATE_EMAIL)) {
            throw new NonRetryableNotificationException("Alamat email tidak valid: {$log->recipient}");
        }

        Mail::to($log->recipient)->send(
            new TemplatedMail(
                subjectText: $log->subject ?? setting('company_name', 'Destinara'),
                bodyHtml: $log->body
            )
        );
    }

    protected function sendWhatsApp(NotificationLog $log, VexaHostClient $waClient): void
    {
        $waEnabled = (bool) setting('wa_enabled', true);
        $apiKey = setting('wa_api_key');

        if (! $waEnabled || empty($apiKey)) {
            // SPEC §10.1: wa_enabled=false atau setting belum diisi → channel WA dilewati dan dicatat failed tanpa crash
            throw new NonRetryableNotificationException('WhatsApp belum dikonfigurasi atau dinonaktifkan di Pengaturan.');
        }

        if (empty($log->recipient)) {
            throw new NonRetryableNotificationException('Nomor WhatsApp tujuan kosong.');
        }

        $providerId = $waClient->sendText(
            to: $log->recipient,
            message: $log->body
        );

        $log->provider_message_id = $providerId;
    }

    protected function sendDatabaseNotification(NotificationLog $log): void
    {
        $user = null;
        if ($log->notifiable_type === User::class && $log->notifiable_id) {
            $user = User::find($log->notifiable_id);
        } elseif (is_numeric($log->recipient)) {
            $user = User::find((int) $log->recipient);
        }

        if ($user) {
            DatabaseNotification::create([
                'id' => Str::uuid()->toString(),
                'type' => 'Destinara\\InAppNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => [
                    'event_key' => $log->event_key,
                    'subject' => $log->subject,
                    'message' => $log->body,
                    'log_id' => $log->id,
                ],
                'read_at' => null,
            ]);
        }
    }
}
