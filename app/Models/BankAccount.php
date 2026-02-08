<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'bank_name',
        'account_number',
        'account_holder',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];
}
