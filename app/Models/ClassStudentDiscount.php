<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassStudentDiscount extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'class_student_id',
        'month',
        'year',
        'discount_type',
        'discount_value',
    ];

    protected $casts = [
        'class_student_id' => 'integer',
        'month' => 'integer',
        'year' => 'integer',
        'discount_value' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(ClassStudent::class, 'class_student_id');
    }
}
