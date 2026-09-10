<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'teacher_id',
        'month',
        'year',
        'sent_by',
        'sent_at',
        'message_snapshot',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public static function forParent(int $parentId, int $month, int $year): ?self
    {
        return static::where('parent_id', $parentId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();
    }

    public static function forTeacher(int $teacherId, int $month, int $year): ?self
    {
        return static::where('teacher_id', $teacherId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();
    }

    public function markSent(User $sender, ?string $messageSnapshot = null): void
    {
        $this->update([
            'sent_by' => $sender->id,
            'sent_at' => now(),
            'message_snapshot' => $messageSnapshot,
        ]);
    }

    public function markUnsent(): void
    {
        $this->update([
            'sent_by' => null,
            'sent_at' => null,
            'message_snapshot' => null,
        ]);
    }
}
