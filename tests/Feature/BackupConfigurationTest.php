<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\User;
use App\Notifications\BackupAlertNotifiable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Tests\TestCase;

/**
 * Audit v11 K-4: backup ke disk yang bisa dikonfigurasi, hanya data aplikasi,
 * alert ke admin_alert_emails, terjadwal lengkap, dan terbaca di System Health.
 */
class BackupConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_sources_exclude_source_code_and_env(): void
    {
        $include = config('backup.backup.source.files.include');

        $this->assertNotContains(base_path(), $include);
        $this->assertContains(storage_path('app/private'), $include);
        $this->assertContains(storage_path('app/public'), $include);
        $this->assertContains(storage_path('app/private/'.config('backup.backup.name')), config('backup.backup.source.files.exclude'));
    }

    public function test_backup_destination_defaults_to_local_and_notifies_only_failures(): void
    {
        $this->assertSame(['local'], config('backup.backup.destination.disks'));
        $this->assertSame(['local'], config('backup.monitor_backups.0.disks'));
        $this->assertSame(BackupAlertNotifiable::class, config('backup.notifications.notifiable'));
        $this->assertNotContains('your@example.com', (array) config('backup.notifications.mail.to'));

        $notifications = config('backup.notifications.notifications');
        $this->assertSame(['mail'], $notifications[BackupHasFailedNotification::class]);
        $this->assertSame([], $notifications[BackupWasSuccessfulNotification::class]);
    }

    public function test_backup_alert_recipients_come_from_admin_alert_emails_setting(): void
    {
        setting(['admin_alert_emails' => ['ops@destinara.test', 'bukan-email', 'owner@destinara.test']]);

        $this->assertSame(
            ['ops@destinara.test', 'owner@destinara.test'],
            (new BackupAlertNotifiable)->routeNotificationForMail()
        );

        setting(['admin_alert_emails' => []]);
        $this->assertSame([], (new BackupAlertNotifiable)->routeNotificationForMail());
    }

    public function test_backup_commands_are_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())->pluck('command')->implode("\n");

        $this->assertStringContainsString('backup:run --only-db', $commands);
        $this->assertStringContainsString('backup:run --only-files', $commands);
        $this->assertStringContainsString('backup:clean', $commands);
        $this->assertStringContainsString('backup:monitor', $commands);
    }

    public function test_system_health_reads_latest_backup_from_destination_disk(): void
    {
        $this->seed();
        Http::fake();

        $admin = User::factory()->create(['account_type' => AccountType::INTERNAL, 'status' => 'active']);
        $admin->assignRole('super_admin');

        // Belum ada arsip
        $this->actingAs($admin)->get(route('admin.health.index'))
            ->assertOk()
            ->assertSee('local: belum ada arsip backup');

        // Ada arsip baru di folder backup milik aplikasi
        Storage::disk('local')->put(config('backup.backup.name').'/2026-10-21-02-00-00.zip', 'zip');

        $this->actingAs($admin)->get(route('admin.health.index'))
            ->assertOk()
            ->assertSee('local: terakhir')
            ->assertSee('isi BACKUP_DISK dengan disk S3/R2');
    }
}
