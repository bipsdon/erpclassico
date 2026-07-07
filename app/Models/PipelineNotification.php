<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineNotification extends Model
{
    protected $table = 'pipeline_notifications';

    protected $fillable = [
        'sent_by',
        'target_department',
        'subject',
        'message',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(PipelineNotificationRead::class, 'notification_id');
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    public function getTargetLabelAttribute(): string
    {
        return match ($this->target_department) {
            'designer'         => 'Design Team',
            'printing_manager' => 'Printing Team',
            'sewing_manager'   => 'Sewing Team',
            'pipeline_manager' => 'Pipeline Manager',
            default            => 'All Departments',
        };
    }

    public function isBroadcast(): bool
    {
        return $this->target_department === null;
    }

    /**
     * Whether this notification is visible to the given user's role.
     */
    public function isVisibleTo(User $user): bool
    {
        if ($this->isBroadcast()) {
            return true;
        }

        if ($user->isPipelineManager()) {
            // PM sees everything — targeted to them, or to any department
            return true;
        }

        return $this->target_department === $user->role;
    }

    /**
     * Whether the given user has read this notification.
     */
    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }
}
