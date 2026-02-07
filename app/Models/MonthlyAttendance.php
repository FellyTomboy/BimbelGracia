<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyAttendance extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'lesson_id',
        'teacher_id',
        'student_id',
        'month',
        'year',
        'dates',
        'notes',
        'total_lessons',
        'status',
        'parent_payment_status',
        'teacher_payment_status',
        'submitted_at',
        'validated_at',
        'validated_by',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'dates' => 'array',
        'total_lessons' => 'integer',
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
        'status' => 'string',
        'parent_payment_status' => 'string',
        'teacher_payment_status' => 'string',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
