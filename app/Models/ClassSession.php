<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClassSession extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'class_group_id',
        'teacher_id',
        'subject',
        'session_date',
        'session_time',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'session_time' => 'datetime:H:i',
    ];

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)
            ->withPivot(['is_present'])
            ->withTimestamps();
    }
}
