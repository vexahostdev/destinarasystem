<?php

namespace App\Domain\Notification\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;

class NotificationDispatcher
{
    public function __construct(
        protected TemplateRenderer $renderer
    ) {}

    /**
     * Dispatch notification across active channels to specified audiences (SPEC §10.1).
     *
     * @param  array  $context  Placeholders and models (e.g. ['booking' => $b, 'user' => $u, 'total_amount' => 'Rp 1.000.000'])
     * @param  array<string>  $audiences  List of audiences ('buyer', 'partner', 'admin')
     * @return array<NotificationLog> Created notification logs
     */
    public function send(string $eventKey, array $context, array $audiences = ['buyer']): array
    {
        $createdLogs = [];

        foreach ($audiences as $audience) {
            $templates = NotificationTemplate::where('event_key', $eventKey)
                ->where('audience', $audience)
                ->where('is_active', true)
                ->get();

            if ($templates->isEmpty()) {
                continue;
            }

            // Resolve recipient info for this audience
            $recipientData = $this->resolveRecipient($audience, $context);

            foreach ($templates as $template) {
                $recipient = match ($template->channel) {
                    NotificationChannel::MAIL => $recipientData['email'] ?? null,
                    NotificationChannel::WHATSAPP => $recipientData['phone'] ?? null,
                    NotificationChannel::DATABASE => $recipientData['user_id'] ?? null,
                };

                if (empty($recipient)) {
                    if ($audience === 'admin') {
                        \Illuminate\Support\Facades\Log::info("Notifikasi admin untuk event '{$eventKey}' pada channel '{$template->channel->value}' dilewati karena penerima belum dikonfigurasi.");
                    }
                    continue;
                }

                $renderedSubject = $template->subject ? $this->renderer->render($template->subject, $context) : null;
                $renderedBody = $this->renderer->render($template->body, $context);

                $log = NotificationLog::create([
                    'notifiable_type' => isset($recipientData['user']) ? User::class : null,
                    'notifiable_id' => $recipientData['user']?->id,
                    'event_key' => $eventKey,
                    'channel' => $template->channel,
                    'recipient' => (string) $recipient,
                    'subject' => $renderedSubject,
                    'body' => $renderedBody,
                    'status' => NotificationStatus::QUEUED,
                ]);

                SendNotificationJob::dispatch($log->id)->afterCommit();
                $createdLogs[] = $log;
            }
        }

        return $createdLogs;
    }

    /**
     * Resolve email, phone, and user ID based on audience and context.
     */
    protected function resolveRecipient(string $audience, array $context): array
    {
        $user = $context['user'] ?? null;
        $booking = $context['booking'] ?? null;

        return match ($audience) {
            'buyer' => [
                'user' => $user ?: $booking?->user,
                'user_id' => $user?->id ?: $booking?->user_id,
                'email' => $context['buyer_email'] ?? $user?->email ?? $booking?->institution?->pic_email ?? $booking?->user?->email,
                'phone' => $context['buyer_phone'] ?? $user?->phone ?? $booking?->institution?->pic_phone ?? $booking?->user?->phone,
            ],
            'partner' => [
                'user' => $booking?->destination?->partner ?: ($context['partner_user'] ?? null),
                'user_id' => $booking?->destination?->partner_id ?: ($context['partner_user']?->id ?? null),
                'email' => $context['partner_email'] ?? $booking?->destination?->partner?->email ?? $context['partner_user']?->email,
                'phone' => $context['partner_phone'] ?? $booking?->destination?->partner?->phone ?? $context['partner_user']?->phone,
            ],
            'admin' => [
                'user' => User::where('account_type', 'internal')->first(),
                'user_id' => User::where('account_type', 'internal')->first()?->id,
                'email' => setting('admin_alert_emails'),
                'phone' => setting('admin_alert_whatsapp'),
            ],
            default => [
                'user' => $user,
                'user_id' => $user?->id,
                'email' => $user?->email,
                'phone' => $user?->phone,
            ],
        };
    }
}
