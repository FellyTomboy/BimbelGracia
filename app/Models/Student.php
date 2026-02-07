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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class)->withTimestamps();
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function monthlyAttendances(): HasMany
    {
        return $this->hasMany(MonthlyAttendance::class);
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
