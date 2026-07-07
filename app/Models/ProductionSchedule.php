<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ProductionSchedule extends Model
{
    protected $fillable = [
        'order_id',
        'department',
        'scheduled_date',
        'quantity_scheduled',
        'is_overtime',
        'completed_at',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date'     => 'date',
            'quantity_scheduled' => 'integer',
            'is_overtime'        => 'boolean',
            'completed_at'       => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    public function getIsCompletedAttribute(): bool
    {
        return $this->completed_at !== null;
    }

    public function getIsOverdueAttribute(): bool
    {
        return ! $this->is_completed
            && $this->scheduled_date->isPast();
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    /** Slots assigned to a specific department on a given date. */
    public function scopeForDate($query, string $department, string $date)
    {
        return $query->where('department', $department)
                     ->where('scheduled_date', $date);
    }

    /** Incomplete slots only. */
    public function scopePending($query)
    {
        return $query->whereNull('completed_at');
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
