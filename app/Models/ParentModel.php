<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentModel extends Model
{
    use HasFactory, Auditable, SoftDeletes;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'name',
        'address',
    ];

    protected $casts = [
        'name' => 'string',
        'address' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    public function getWhatsappAttribute(): ?string
    {
        return $this->user?->phone;
    }
}