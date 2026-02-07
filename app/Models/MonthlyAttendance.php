<?php

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
        'month',
        'year',
        'dates',
        'notes',
        'total_lessons',
        'status_validation',
        'parent_payment_status',
        'teacher_payment_status',
        'validated_at',
        'validated_by',
        'created_by',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'dates' => 'array',
        'total_lessons' => 'integer',
        'validated_at' => 'datetime',
        'status_validation' => 'string',
        'parent_payment_status' => 'string',
        'teacher_payment_status' => 'string',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
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
}
