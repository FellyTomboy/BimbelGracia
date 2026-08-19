<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'access_type',
        'access_password',
        'access_password_plain',
        'uploaded_by',
        'protection_level',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'document_teacher')
            ->withTimestamps();
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(DocumentAccessLog::class);
    }

    /**
     * Determine whether the given user can access this document.
     *
     * Server-side authorization check. Admin always has access.
     */
    public function canBeAccessedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Admin always has full access.
        if ($user->role->value === 'admin') {
            return true;
        }

        // Only guru (and admin) may access documents.
        if ($user->role->value !== 'guru') {
            return false;
        }

        if ($this->access_type === 'password') {
            // Password-protected documents are unlocked via session.
            return session()->get('document_unlocked_' . $this->id, false) === true;
        }

        // teacher access type: must be assigned to this teacher.
        $teacher = $user->teacher;

        return $teacher !== null
            && $this->teachers()->where('teacher_id', $teacher->id)->exists();
    }

    /**
     * Whether the document is visible in the guru document list.
     * Password documents are visible to all gurus (they need the password to open).
     * Teacher documents are only visible to assigned teachers.
     */
    public function isVisibleTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->role->value === 'admin') {
            return true;
        }

        if ($user->role->value !== 'guru') {
            return false;
        }

        if ($this->access_type === 'password') {
            return true;
        }

        $teacher = $user->teacher;

        return $teacher !== null
            && $this->teachers()->where('teacher_id', $teacher->id)->exists();
    }

    public function isStrict(): bool
    {
        return ($this->protection_level ?? 'standard') === 'strict';
    }

    public function isStandard(): bool
    {
        return ($this->protection_level ?? 'standard') === 'standard';
    }

    public function getFormattedSizeAttribute(): string
    {
        if (! $this->file_size) return '-';
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}