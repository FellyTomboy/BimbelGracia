<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\FormatsWhatsappNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonOffer extends Model
{
    use HasFactory, SoftDeletes, FormatsWhatsappNumber, Auditable;

    protected $fillable = [
        'code',
        'student_id',
        'subject',
        'schedule_day',
        'schedule_time',
        'note',
        'status',
        'contact_whatsapp',
        'created_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function setContactWhatsappAttribute(?string $value): void
    {
        $this->attributes['contact_whatsapp'] = $this->normalizeWhatsapp($value);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
