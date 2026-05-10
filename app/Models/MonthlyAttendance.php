<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyAttendance extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'enrollment_attendances';

    protected $fillable = [
        'enrollment_id',
        'lesson_date',
        'month',
        'year',
        'notes',
        'image',
        'status_validation',
        'parent_payment_status',
        'teacher_payment_status',
        'validated_at',
        'validated_by',
        'created_by',
        'parent_rate',
        'teacher_rate',
    ];

    protected $casts = [
        'lesson_date' => 'date',
        'month' => 'integer',
        'year' => 'integer',
        'validated_at' => 'datetime',
        'status_validation' => 'string',
        'parent_payment_status' => 'string',
        'teacher_payment_status' => 'string',
        'parent_rate' => 'integer',
        'teacher_rate' => 'integer',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    protected static function booted()
    {
        static::creating(function ($attendance) {
            // Store rates at time of validation for historical accuracy
            if (! $attendance->parent_rate && $attendance->enrollment) {
                $attendance->parent_rate = $attendance->enrollment->parent_rate;
            }
            if (! $attendance->teacher_rate && $attendance->enrollment) {
                $attendance->teacher_rate = $attendance->enrollment->teacher_rate;
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
