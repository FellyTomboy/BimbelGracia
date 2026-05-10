<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\FormatsWhatsappNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassStudent extends Model
{
    use HasFactory, SoftDeletes, FormatsWhatsappNumber, Auditable;

    protected $fillable = [
        'name',
        'whatsapp_primary',
        'whatsapp_secondary',
        'rate_per_meeting',
        'status',
        'notes',
    ];

    protected $casts = [
        'rate_per_meeting' => 'integer',
        'status' => 'string',
    ];

    public function setWhatsappPrimaryAttribute(?string $value): void
    {
        $this->attributes['whatsapp_primary'] = $this->normalizeWhatsapp($value);
    }

    public function setWhatsappSecondaryAttribute(?string $value): void
    {
        $this->attributes['whatsapp_secondary'] = $this->normalizeWhatsapp($value);
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassStudentSession::class,
            'class_student_session_student',
            'class_student_id',
            'class_student_session_id'
        )->withTimestamps();
    }

    protected static function booted()
    {
        static::saved(function () {
            app(\App\Services\MonthlySnapshotSyncService::class)->syncAll();
        });
        static::deleted(function () {
            app(\App\Services\MonthlySnapshotSyncService::class)->syncAll();
        });
        static::restored(function () {
            app(\App\Services\MonthlySnapshotSyncService::class)->syncAll();
        });
    }
}
