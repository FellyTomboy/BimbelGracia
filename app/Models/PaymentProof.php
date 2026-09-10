<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProof extends Model
{
    protected $fillable = [
        'parent_id',
        'month',
        'year',
        'proof_path',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'reviewed_at' => 'datetime',
        'reviewed_by' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
