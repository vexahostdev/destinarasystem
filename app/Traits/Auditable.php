<?php

namespace App\Traits;

use App\Domain\Audit\AuditLogger;
use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            // Avoid logging audit_logs or recursive triggers
            if ($model instanceof AuditLog) {
                return;
            }

            AuditLogger::log(
                action: 'created',
                target: $model,
                description: class_basename($model).' #'.$model->getKey().' dibuat',
                oldValues: null,
                newValues: $model->getAttributes()
            );
        });

        static::updated(function ($model) {
            if ($model instanceof AuditLog) {
                return;
            }

            $changes = $model->getChanges();
            // Don't log if only timestamps changed
            unset($changes['updated_at']);

            if (empty($changes)) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $changes);

            AuditLogger::log(
                action: 'updated',
                target: $model,
                description: class_basename($model).' #'.$model->getKey().' diperbarui',
                oldValues: $original,
                newValues: $changes
            );
        });

        static::deleted(function ($model) {
            if ($model instanceof AuditLog) {
                return;
            }

            AuditLogger::log(
                action: 'deleted',
                target: $model,
                description: class_basename($model).' #'.$model->getKey().' dihapus',
                oldValues: $model->getOriginal(),
                newValues: null
            );
        });
    }
}
