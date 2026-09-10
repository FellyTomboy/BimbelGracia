<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = ['parent_id', 'month', 'year', 'filename', 'regenerated_at'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class);
    }
}
