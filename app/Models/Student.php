<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\FormatsWhatsappNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory, SoftDeletes, FormatsWhatsappNumber, Auditable;

    protected $fillable = [
        'parent_id',
        'nickname',
        'full_name',
        'address',
        'status',
    ];

    protected $appends = ['display_name'];

    protected $casts = [
        'status' => 'string',
    ];

    public function getNameAttribute($value)
    {
        return $this->full_name ?: $this->nickname ?: '';
    }

    public function getDisplayNameAttribute(): string
    {
        return trim((string) ($this->full_name ?: $this->nickname ?: '')) ?: 'Tanpa nama';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class)->withTimestamps();
    }

    public function enrollments(): BelongsToMany
    {
        return $this->belongsToMany(Enrollment::class, 'enrollment_student')
            ->withTimestamps();
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