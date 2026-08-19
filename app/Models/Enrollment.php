<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    private ?\App\Services\AttendanceFineService $fineService = null;

    private function fines(): \App\Services\AttendanceFineService
    {
        return $this->fineService ??= app(\App\Services\AttendanceFineService::class);
    }

    protected $fillable = [
        'program_id',
        'type',
        'teacher_id',
        'parent_rate',
        'teacher_rate',
        'pricing_tiers',
        'agreed_sessions_per_month',
        'validation_status',
        'status',
    ];

    protected $casts = [
        'type' => 'string',
        'parent_rate' => 'integer',
        'teacher_rate' => 'integer',
        'pricing_tiers' => 'array',
        'agreed_sessions_per_month' => 'integer',
        'validation_status' => 'integer',
        'status' => 'string',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class)->withTrashed();
    }

    public function isKelas(): bool
    {
        return strtolower((string) ($this->type ?? 'privat')) === 'kelas';
    }

    public function isPrivat(): bool
    {
        return ! $this->isKelas();
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class)->withTrashed();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollment_student')
            ->withTimestamps()
            ->withTrashed();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(MonthlyAttendance::class, 'enrollment_id');
    }

    /**
     * Get parent rate based on number of present students using pricing tiers.
     */
    public function getParentRateForCount(int $presentCount): int
    {
        $tiers = $this->pricing_tiers;
        if ($tiers && isset($tiers['parent_rate'])) {
            $rates = $tiers['parent_rate'];
            $closestRate = (int) ($rates['1'] ?? $this->parent_rate);
            for ($i = $presentCount; $i >= 1; $i--) {
                if (isset($rates[(string) $i])) {
                    $closestRate = (int) $rates[(string) $i];
                    break;
                }
            }
            return $closestRate;
        }
        return (int) $this->parent_rate;
    }

    /**
     * Get teacher rate based on number of present students using pricing tiers.
     */
    public function getTeacherRateForCount(int $presentCount): int
    {
        $tiers = $this->pricing_tiers;
        if ($tiers && isset($tiers['teacher_rate'])) {
            $rates = $tiers['teacher_rate'];
            $closestRate = (int) ($rates['1'] ?? $this->teacher_rate);
            for ($i = $presentCount; $i >= 1; $i--) {
                if (isset($rates[(string) $i])) {
                    $closestRate = (int) $rates[(string) $i];
                    break;
                }
            }
            return $closestRate;
        }
        return (int) $this->teacher_rate;
    }

    /**
     * Get parent rate with attendance penalty applied.
     * If student attends less than 50% of agreed sessions, base rate increases by Rp 5.000.
     * Note: only applies to privat enrollments. For kelas, billing uses fixed 50% rule, not this method.
     */
    public function applyAttendancePenaltyParent(int $presentCount, int $totalSessionsThisMonth, int $studentTotalPresent): int
    {
        if (! $this->fines()->isAttendancePenaltyEnabled()) {
            return $this->getParentRateForCount($presentCount);
        }
        $baseRate = $this->getParentRateForCount($presentCount);

        if ($this->hasAttendancePenalty($totalSessionsThisMonth, $studentTotalPresent)) {
            return $baseRate + 5000;
        }

        return $baseRate;
    }

    /**
     * Get teacher rate with attendance penalty applied.
     * If student attends less than 50% of agreed sessions, base rate increases by Rp 5.000.
     * Note: only applies to privat enrollments. For kelas, billing uses fixed rate.
     */
    public function applyAttendancePenaltyTeacher(int $presentCount, int $totalSessionsThisMonth, int $studentTotalPresent): int
    {
        if (! $this->fines()->isAttendancePenaltyEnabled()) {
            return $this->getTeacherRateForCount($presentCount);
        }
        $baseRate = $this->getTeacherRateForCount($presentCount);

        if ($this->hasAttendancePenalty($totalSessionsThisMonth, $studentTotalPresent)) {
            return $baseRate + 5000;
        }

        return $baseRate;
    }

    /**
     * Check if penalty applies for a student in a given month.
     */
    public function hasAttendancePenalty(int $totalSessionsThisMonth, int $studentTotalPresent): bool
    {
        $agreed = $this->agreed_sessions_per_month ?? 4;
        return $totalSessionsThisMonth > 0 && $studentTotalPresent > 0 && $studentTotalPresent < ($agreed / 2);
    }

    /**
     * Count present students for an attendance record.
     */
    public function countPresentStudents(MonthlyAttendance $attendance): int
    {
        return $attendance->students
            ->filter(fn ($s) => ($s->pivot->total_present ?? 0) > 0)
            ->count();
    }

    protected static function booted()
    {
        // WARNING: This fires on EVERY save/delete/restore — triggers a full snapshot sync
        // for the current month. Acceptable for now since syncAll() only counts active records
        // (no filtering by period), but could become a bottleneck at scale.
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