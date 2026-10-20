<?php

namespace Tests\Feature;

use App\Domain\Audit\AuditLogger;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_audit_log_records_and_masks_sensitive_data(): void
    {
        $user = User::factory()->create();

        AuditLogger::log(
            action: 'settings.update',
            target: $user,
            description: 'Mengubah konfigurasi payment gateway',
            oldValues: ['xendit_secret_key' => 'old_secret_123', 'status' => 'inactive'],
            newValues: ['xendit_secret_key' => 'new_secret_456', 'status' => 'active'],
            userId: $user->id
        );

        $log = AuditLog::where('action', 'settings.update')->first();

        $this->assertNotNull($log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('********', $log->old_values['xendit_secret_key']);
        $this->assertEquals('********', $log->new_values['xendit_secret_key']);
        $this->assertEquals('active', $log->new_values['status']);
    }

    public function test_automatic_model_auditing_logs_changes(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        // Check created log
        $createdLog = AuditLog::where('action', 'created')
            ->where('target_type', User::class)
            ->where('target_id', $user->id)
            ->first();
        $this->assertNotNull($createdLog);

        $user->update(['name' => 'Updated Name']);

        // Check updated log
        $updatedLog = AuditLog::where('action', 'updated')
            ->where('target_type', User::class)
            ->where('target_id', $user->id)
            ->first();

        $this->assertNotNull($updatedLog);
        $this->assertEquals('Original Name', $updatedLog->old_values['name']);
        $this->assertEquals('Updated Name', $updatedLog->new_values['name']);
    }
}
