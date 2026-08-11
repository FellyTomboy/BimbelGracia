<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TeacherRegistrant extends Model
{
    protected $table = 'teacher_registrants';

    protected $fillable = [
        'name',
        'whatsapp',
        'major',
        'subjects',
        'address',
        'bank_name',
        'bank_account',
        'bank_owner',
        'token',
        'converted',
    ];

    protected $casts = [
        'converted' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function (TeacherRegistrant $registrant) {
            if (!$registrant->token) {
                $registrant->token = Str::random(32);
            }
        });
    }

    public function getFormUrlAttribute(): string
    {
        return route('register-teacher.form', $this->token);
    }
}