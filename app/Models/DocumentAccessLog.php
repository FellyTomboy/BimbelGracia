<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAccessLog extends Model
{
    protected $fillable = [
<<<<<<< HEAD
        'user_id',
        'document_id',
        'action',
        'ip_address',
        'user_agent',
        'accessed_at',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];
=======
        'document_id',
        'user_id',
        'teacher_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
>>>>>>> 1a30744 (feat: secure document storage and add download with access logging)

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

<<<<<<< HEAD
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
=======
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
>>>>>>> 1a30744 (feat: secure document storage and add download with access logging)
