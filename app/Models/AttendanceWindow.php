<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceWindow extends Model
{
    use HasFactory, Auditable;

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

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
}
