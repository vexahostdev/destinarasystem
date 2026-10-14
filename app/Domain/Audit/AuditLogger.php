<?php

namespace App\Domain\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log an explicit business action.
     */
    public static function log(
        string $action,
        ?Model $target = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): AuditLog {
        $actorId = $userId ?? Auth::id();

        return AuditLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target?->getKey(),
            'old_values' => $oldValues ? self::maskSensitiveFields($oldValues) : null,
            'new_values' => $newValues ? self::maskSensitiveFields($newValues) : null,
            'description' => $description,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Mask sensitive fields in attributes array.
     */
    public static function maskSensitiveFields(array $attributes): array
    {
        $sensitiveKeys = [
            'password',
            'remember_token',
            'secret_key',
            'xendit_secret_key',
            'xendit_callback_token',
            'midtrans_server_key',
            'smtp_password',
            'wa_api_key',
            'wa_webhook_secret',
            'token',
        ];

        foreach ($attributes as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys, true)) {
                $attributes[$key] = '********';
            } elseif (is_array($value)) {
                $attributes[$key] = self::maskSensitiveFields($value);
            }
        }

        return $attributes;
    }
}
