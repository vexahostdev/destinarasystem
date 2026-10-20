<?php

namespace App\Notifications;

use Spatie\Backup\Notifications\Notifiable;

/**
 * Penerima notifikasi spatie/laravel-backup.
 * Alamat email diambil dari setting admin_alert_emails agar bisa diubah dari panel admin.
 */
class BackupAlertNotifiable extends Notifiable
{
    /** @return array<int, string> */
    public function routeNotificationForMail(): string|array
    {
        $emails = setting('admin_alert_emails', []);

        if (is_string($emails)) {
            $emails = json_decode($emails, true) ?? array_map('trim', explode(',', $emails));
        }

        return array_values(array_filter((array) $emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
    }
}
