<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            $model->writeAudit('created', [], $model->getAttributes());
        });

        static::updated(function (Model $model): void {
            $model->writeAudit('updated', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function (Model $model): void {
            $model->writeAudit('deleted', $model->getOriginal(), []);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model): void {
                $model->writeAudit('restored', $model->getOriginal(), $model->getAttributes());
            });
        }
    }

    protected function writeAudit(string $action, array $before, array $after): void
    {
        if ($this instanceof AuditLog) {
            return;
        }

        $exclude = [];
        if (property_exists($this, 'auditExclude') && is_array($this->auditExclude)) {
            $exclude = $this->auditExclude;
        }
        $beforeFiltered = collect($before)->except($exclude)->toArray();
        $afterFiltered = collect($after)->except($exclude)->toArray();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $this->getMorphClass(),
            'auditable_id' => $this->getKey(),
            'before' => $beforeFiltered,
            'after' => $afterFiltered,
        ]);
    }
}
