<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassStudentSession extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'class_student_ids',
        'session_date',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'class_student_ids' => 'array',
        'session_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];
}
