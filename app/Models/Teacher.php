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

class Teacher extends Model
{
    use HasFactory, SoftDeletes, FormatsWhatsappNumber, Auditable;

    protected $fillable = [
        'user_id',
        'nickname',
        'full_name',
        'whatsapp',
        'whatsapp_number',
        'major',
        'subjects',
        'address',
        'bank_name',
        'bank_account',
        'bank_owner',
        'class_rate',
        'profile_photo_path',
        'profile_photo_approved',
        'status',
        'is_founder',
        'founder_description',
    ];

    protected $casts = [
        'status' => 'string',
        'class_rate' => 'integer',
        'profile_photo_approved' => 'boolean',
    ];

    public function getDisplayNameAttribute(): string
    {
        return trim((string) ($this->full_name ?: $this->nickname ?: '')) ?: 'Tanpa nama';
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : null;
    }

    public function setWhatsappAttribute(?string $value): void
    {
        $this->attributes['whatsapp'] = $this->normalizeWhatsapp($value);
    }

    public function setWhatsappNumberAttribute(?string $value): void
    {
        $this->attributes['whatsapp_number'] = $this->normalizeWhatsapp($value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    protected static function booted()
    {
        // WARNING: Triggers full MonthlySnapshotSyncService::syncAll() on every teacher
        // save/delete/restore. See Enrollment::booted for details.
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