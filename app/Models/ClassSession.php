<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'program_id',
        'session_date',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'class_session_teacher')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(MonthlyAttendance::class, 'class_session_id');
    }
}
