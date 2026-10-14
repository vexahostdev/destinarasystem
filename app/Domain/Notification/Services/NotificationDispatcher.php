<?php

namespace App\Domain\Notification\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Collection;

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

            // Resolve recipient info for buyer / partner
            $recipientData = $this->resolveRecipient($audience, $context);

            foreach ($templates as $template) {
                // Audience Admin: Channel DATABASE -> disebarkan ke seluruh staf internal berizin
                if ($audience === 'admin' && $template->channel === NotificationChannel::DATABASE) {
                    $internalUsers = $this->resolveInternalUsersForEvent($eventKey);
                    foreach ($internalUsers as $adminUser) {
                        $renderedSubject = $template->subject ? $this->renderer->render($template->subject, $context) : null;
                        $renderedBody = $this->renderer->render($template->body, $context);

                        $log = NotificationLog::create([
                            'notifiable_type' => User::class,
                            'notifiable_id' => $adminUser->id,
                            'event_key' => $eventKey,
                            'channel' => $template->channel,
                            'recipient' => (string) $adminUser->id,
                            'subject' => $renderedSubject,
                            'body' => $renderedBody,
                            'status' => NotificationStatus::QUEUED,
                        ]);

                        SendNotificationJob::dispatch($log->id)->afterCommit();
                        $createdLogs[] = $log;
                    }

                    continue;
                }

                // Audience Admin: Channel MAIL -> dikirim ke daftar admin_alert_emails
                if ($audience === 'admin' && $template->channel === NotificationChannel::MAIL) {
                    $alertEmails = setting('admin_alert_emails', ['admin@destinara.my.id']);
                    if (is_string($alertEmails)) {
                        $alertEmails = json_decode($alertEmails, true) ?: array_map('trim', explode(',', $alertEmails));
                    }
                    $alertEmails = array_filter((array) $alertEmails);
                    if (empty($alertEmails)) {
                        $defaultAdmin = User::where('account_type', 'internal')->first();
                        if ($defaultAdmin?->email) {
                            $alertEmails = [$defaultAdmin->email];
                        }
                    }

                    foreach ($alertEmails as $email) {
                        $renderedSubject = $template->subject ? $this->renderer->render($template->subject, $context) : null;
                        $renderedBody = $this->renderer->render($template->body, $context);

                        $log = NotificationLog::create([
                            'notifiable_type' => null,
                            'notifiable_id' => null,
                            'event_key' => $eventKey,
                            'channel' => $template->channel,
                            'recipient' => (string) $email,
                            'subject' => $renderedSubject,
                            'body' => $renderedBody,
                            'status' => NotificationStatus::QUEUED,
                        ]);

                        SendNotificationJob::dispatch($log->id)->afterCommit();
                        $createdLogs[] = $log;
                    }

                    continue;
                }

                // Audience Admin: Channel WHATSAPP -> dikirim ke daftar admin_alert_whatsapp
                if ($audience === 'admin' && $template->channel === NotificationChannel::WHATSAPP) {
                    $alertPhones = setting('admin_alert_whatsapp', []);
                    if (is_string($alertPhones)) {
                        $alertPhones = json_decode($alertPhones, true) ?: array_map('trim', explode(',', $alertPhones));
                    }
                    $alertPhones = array_filter((array) $alertPhones);
                    if (empty($alertPhones)) {
                        $defaultAdmin = User::where('account_type', 'internal')->first();
                        if ($defaultAdmin?->phone) {
                            $alertPhones = [$defaultAdmin->phone];
                        }
                    }

                    foreach ($alertPhones as $phone) {
                        $renderedSubject = $template->subject ? $this->renderer->render($template->subject, $context) : null;
                        $renderedBody = $this->renderer->render($template->body, $context);

                        $log = NotificationLog::create([
                            'notifiable_type' => null,
                            'notifiable_id' => null,
                            'event_key' => $eventKey,
                            'channel' => $template->channel,
                            'recipient' => (string) $phone,
                            'subject' => $renderedSubject,
                            'body' => $renderedBody,
                            'status' => NotificationStatus::QUEUED,
                        ]);

                        SendNotificationJob::dispatch($log->id)->afterCommit();
                        $createdLogs[] = $log;
                    }

                    continue;
                }

                $recipient = match ($template->channel) {
                    NotificationChannel::MAIL => $recipientData['email'] ?? null,
                    NotificationChannel::WHATSAPP => $recipientData['phone'] ?? null,
                    NotificationChannel::DATABASE => $recipientData['user_id'] ?? null,
                };

                if (empty($recipient)) {
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
                'user_id' => $booking?->destination?->partner_id ?: (($context['partner_user'] ?? null)?->id),
                'email' => $context['partner_email'] ?? $booking?->destination?->partner?->email ?? (($context['partner_user'] ?? null)?->email),
                'phone' => $context['partner_phone'] ?? $booking?->destination?->partner?->phone ?? $booking?->destination?->partner?->partnerProfile?->pic_phone ?? (($context['partner_user'] ?? null)?->phone) ?? (($context['partner_user'] ?? null)?->partnerProfile?->pic_phone),
            ],
            'admin' => [
                'user' => User::where('account_type', 'internal')->first(),
                'user_id' => User::where('account_type', 'internal')->first()?->id,
                'email' => is_array(setting('admin_alert_emails')) ? (setting('admin_alert_emails')[0] ?? null) : setting('admin_alert_emails'),
                'phone' => is_array(setting('admin_alert_whatsapp')) ? (setting('admin_alert_whatsapp')[0] ?? null) : setting('admin_alert_whatsapp'),
            ],
            default => [
                'user' => $user,
                'user_id' => $user?->id,
                'email' => $user?->email,
                'phone' => $user?->phone,
            ],
        };
    }

    /**
     * Resolve internal users who have permission related to the event.
     */
    protected function resolveInternalUsersForEvent(string $eventKey): Collection
    {
        $requiredPermission = match (true) {
            str_starts_with($eventKey, 'booking.') || str_starts_with($eventKey, 'payment.') || $eventKey === 'partner.confirm_reminder' => 'bookings.view',
            str_starts_with($eventKey, 'refund.') => 'refunds.view',
            str_starts_with($eventKey, 'payout.') => 'payouts.view',
            str_starts_with($eventKey, 'destination.') => 'destinations.moderate',
            str_starts_with($eventKey, 'partner.') => 'partners.verify',
            str_starts_with($eventKey, 'institution.') => 'institutions.verify',
            str_starts_with($eventKey, 'system.') => 'settings.general',
            default => 'bookings.view',
        };

        $internalUsers = User::where('account_type', 'internal')->get();
        $filtered = $internalUsers->filter(function (User $user) use ($requiredPermission) {
            return $user->hasRole('super_admin') || $user->hasPermissionTo($requiredPermission);
        });

        return $filtered->isNotEmpty() ? $filtered : $internalUsers;
    }
}
