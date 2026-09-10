<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'division',
        'type',
        'subject',
        'description',
        'default_parent_rate',
        'default_teacher_rate',
        'status',
    ];

    protected $casts = [
        'default_parent_rate' => 'integer',
        'default_teacher_rate' => 'integer',
        'status' => 'string',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function teacherRates(): HasMany
    {
        return $this->hasMany(TeacherProgramRate::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_program_rates')
            ->withPivot('rate')
            ->withTimestamps();
    }

    public function isKelas(): bool
    {
        return strtolower((string) ($this->type ?? 'privat')) === 'kelas';
    }

    protected static function booted()
    {
        // WARNING: Triggers full MonthlySnapshotSyncService::syncAll() on every program
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
