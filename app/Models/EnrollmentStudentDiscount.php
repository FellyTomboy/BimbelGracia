<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentStudentDiscount extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'enrollment_id',
        'student_id',
        'month',
        'year',
        'discount_type',
        'discount_value',
    ];

    protected $casts = [
        'enrollment_id' => 'integer',
        'student_id' => 'integer',
        'month' => 'integer',
        'year' => 'integer',
        'discount_value' => 'integer',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
