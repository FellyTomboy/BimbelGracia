<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassStudentSession extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
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

    /**
     * Relasi ke banyak murid melalui tabel pivot.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassStudent::class, 
            'class_student_session_student',
            'class_student_session_id',
            'class_student_id'
        )->withTimestamps();
    }
}