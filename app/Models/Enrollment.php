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

    protected $fillable = [
        'program_id',
        'teacher_id',
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

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollment_student')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(MonthlyAttendance::class, 'enrollment_id');
    }
}
