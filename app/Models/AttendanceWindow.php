<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'year',
        'is_open',
        'opened_by',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'is_open' => 'boolean',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];
}