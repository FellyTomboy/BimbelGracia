<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewStudent extends Model
{
    protected $table = 'new_students';

    protected $fillable = [
        'name',
        'whatsapp',
        'parent_name',
        'parent_whatsapp',
        'school',
        'grade',
        'division',
        'notes',
        'token',
        'converted',
    ];

    protected $casts = [
        'converted' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function (NewStudent $newStudent) {
            if (!$newStudent->token) {
                $newStudent->token = Str::random(32);
            }
        });
    }

    public function getFormUrlAttribute(): string
    {
        return route('register-student.form', $this->token);
    }
}