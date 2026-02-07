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

class Student extends Model
{
    use HasFactory, SoftDeletes, FormatsWhatsappNumber, Auditable;

    protected $fillable = [
        'user_id',
        'name',
        'whatsapp',
        'whatsapp_primary',
        'whatsapp_secondary',
        'address',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function setWhatsappAttribute(?string $value): void
    {
        $this->attributes['whatsapp'] = $this->normalizeWhatsapp($value);
    }

    public function setWhatsappPrimaryAttribute(?string $value): void
    {
        $this->attributes['whatsapp_primary'] = $this->normalizeWhatsapp($value);
    }

    public function setWhatsappSecondaryAttribute(?string $value): void
    {
        $this->attributes['whatsapp_secondary'] = $this->normalizeWhatsapp($value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class)->withTimestamps();
    }

    public function enrollments(): BelongsToMany
    {
        return $this->belongsToMany(Enrollment::class, 'enrollment_student')
            ->withTimestamps();
    }

    public function monthlyAttendances(): HasMany
    {
        return $this->hasMany(MonthlyAttendance::class, 'student_id');
    }

    public function classSessions(): BelongsToMany
    {
        return $this->belongsToMany(ClassSession::class)->withTimestamps();
    }

    public function classGroups(): BelongsToMany
    {
        return $this->belongsToMany(ClassGroup::class)->withTimestamps();
    }
}
