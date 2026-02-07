<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'code',
        'teacher_id',
        'student_id',
        'parent_rate',
        'teacher_rate',
        'validation_status',
        'status',
    ];

    protected $casts = [
        'parent_rate' => 'integer',
        'teacher_rate' => 'integer',
        'validation_status' => 'integer',
        'status' => 'string',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function monthlyAttendances(): HasMany
    {
        return $this->hasMany(MonthlyAttendance::class);
    }
}
