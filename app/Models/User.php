<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ──────────────────────────────────────────────
    // Casts
    // ──────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────
    // Role helpers
    // ──────────────────────────────────────────────

    public function isPipelineManager(): bool
    {
        return $this->role === 'pipeline_manager';
    }

    public function isDesigner(): bool
    {
        return $this->role === 'designer';
    }

    public function isPrintingManager(): bool
    {
        return $this->role === 'printing_manager';
    }

    public function isSewingManager(): bool
    {
        return $this->role === 'sewing_manager';
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles);
    }

    /**
     * Human-readable role label for display in views.
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'pipeline_manager'  => 'Pipeline Manager',
            'designer'          => 'Designer',
            'printing_manager'  => 'Printing Manager',
            'sewing_manager'    => 'Sewing Manager',
            default             => ucfirst($this->role),
        };
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /** Orders this user created. */
    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    /** Attachments this user uploaded. */
    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(OrderAttachment::class, 'uploaded_by');
    }

    /** Schedule slots this user completed. */
    public function completedSchedules(): HasMany
    {
        return $this->hasMany(ProductionSchedule::class, 'completed_by');
    }

    /** Stage transitions this user triggered. */
    public function stageLogs(): HasMany
    {
        return $this->hasMany(OrderStageLog::class, 'changed_by');
    }
}
