<?php

namespace App\Models\Concerns;

use App\Services\AuditLogService;

/**
 * Opt-in trait for models whose create/update/delete should be recorded in
 * the audit log automatically. Add `protected array $auditable = [...]` on
 * the model to limit which changed attributes are captured (default: all
 * fillable attributes).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            app(AuditLogService::class)->log('created', $model, $model->only($model->auditableAttributes()));
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if (empty($changes)) {
                return;
            }

            app(AuditLogService::class)->log('updated', $model, [
                'changed' => array_keys($changes),
                'new_values' => array_intersect_key($changes, array_flip($model->auditableAttributes())),
            ]);
        });

        static::deleted(function ($model) {
            app(AuditLogService::class)->log('deleted', $model);
        });
    }

    public function auditableAttributes(): array
    {
        return property_exists($this, 'auditable') ? $this->auditable : $this->getFillable();
    }
}
