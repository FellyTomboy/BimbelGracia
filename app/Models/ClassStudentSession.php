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
        'class_student_id',
        'session_date',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(ClassStudent::class, 'class_student_id');
    }
}
