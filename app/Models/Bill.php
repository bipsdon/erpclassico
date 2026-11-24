<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'received',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'   => 'float',
            'received' => 'float',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ──────────────────────────────────────────────
    // Computed
    // ──────────────────────────────────────────────

    public function getBalanceAttribute(): float
    {
        return $this->amount - $this->received;
    }
}
