<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\FormatsWhatsappNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory, SoftDeletes, FormatsWhatsappNumber, Auditable;

    protected $fillable = [
        'user_id',
        'name',
        'whatsapp',
        'whatsapp_number',
        'major',
        'subjects',
        'address',
        'bank_name',
        'bank_account',
        'bank_owner',
        'class_rate',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'class_rate' => 'integer',
    ];

    public function setWhatsappAttribute(?string $value): void
    {
        $this->attributes['whatsapp'] = $this->normalizeWhatsapp($value);
    }

    public function setWhatsappNumberAttribute(?string $value): void
    {
        $this->attributes['whatsapp_number'] = $this->normalizeWhatsapp($value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class)->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function monthlyAttendances(): HasMany
    {
        return $this->hasMany(MonthlyAttendance::class);
    }

    public function classTeacherSessions(): HasMany
    {
        return $this->hasMany(ClassTeacherSession::class);
    }
}
