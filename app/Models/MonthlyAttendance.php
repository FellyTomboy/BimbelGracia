<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Services\AttendanceFineService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MonthlyAttendance extends Model
{
    use HasFactory, Auditable;

    protected $table = 'enrollment_attendances';

    protected $fillable = [
        'enrollment_id',
        'class_session_id',
        'session_teacher_id',
        'lesson_date',
        'month',
        'year',
        'notes',
        'image',
        'status_validation',
        'parent_payment_status',
        'teacher_payment_status',
        'parent_review_status',
        'parent_reviewed_at',
        'parent_rejection_reason',
        'validated_at',
        'validated_by',
        'created_by',
        'parent_rate',
        'teacher_rate',
        'agreed_sessions_per_month',
        'attendance_penalty_type',
        'attendance_penalty_value',
        'late_penalty_type',
        'late_penalty_value',
        'payment_proof',
        'payment_proof_status',
    ];

    protected $casts = [
        'lesson_date' => 'date',
        'month' => 'integer',
        'year' => 'integer',
        'validated_at' => 'datetime',
        'status_validation' => 'string',
        'parent_payment_status' => 'string',
        'teacher_payment_status' => 'string',
        'parent_reviewed_at' => 'datetime',
        'parent_rate' => 'integer',
        'teacher_rate' => 'integer',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class)->withTrashed();
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessionTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'session_teacher_id')->withTrashed();
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'attendance_student', 'attendance_id', 'student_id')
            ->withPivot(['total_present'])
            ->withTimestamps();
    }

    public function getTotalLessonsAttribute(): int
    {
        if (!$this->relationLoaded('students')) {
            return 0;
        }
        return (int) $this->students->sum('pivot.total_present');
    }

    protected static function booted()
    {
        static::creating(function ($attendance) {
            $fineService = app(AttendanceFineService::class);

            // Store rates at time of validation for historical accuracy
            if (! $attendance->parent_rate && $attendance->enrollment) {
                $attendance->parent_rate = $attendance->enrollment->parent_rate;
            }
            if (! $attendance->teacher_rate && $attendance->enrollment) {
                $attendance->teacher_rate = $attendance->enrollment->teacher_rate;
            }
            // Store agreed_sessions_per_month snapshot for historical accuracy
            if ($attendance->agreed_sessions_per_month === null && $attendance->enrollment) {
                $attendance->agreed_sessions_per_month = $attendance->enrollment->agreed_sessions_per_month;
            }
            // Store fine settings snapshot at creation time
            if (! $attendance->attendance_penalty_type) {
                $attendance->attendance_penalty_type = $fineService->getAttendancePenaltyType();
            }
            if (! $attendance->attendance_penalty_value && $attendance->attendance_penalty_value !== '0') {
                $attendance->attendance_penalty_value = $fineService->getAttendancePenaltyValue();
            }
            if (! $attendance->late_penalty_type) {
                $attendance->late_penalty_type = $fineService->getLatePenaltyType();
            }
            if (! $attendance->late_penalty_value && $attendance->late_penalty_value !== '0') {
                $attendance->late_penalty_value = $fineService->getLatePenaltyValue();
            }
            // Auto-set month/year from lesson_date
            if ($attendance->lesson_date && ! $attendance->month) {
                $attendance->month = $attendance->lesson_date->month;
            }
            if ($attendance->lesson_date && ! $attendance->year) {
                $attendance->year = $attendance->lesson_date->year;
            }
        });
    }
}
