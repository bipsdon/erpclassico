<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
        'notes',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function bills(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Bill::class, Order::class);
    }

    // ──────────────────────────────────────────────
    // Computed helpers
    // ──────────────────────────────────────────────

    /**
     * Total billed across all orders for this client.
     */
    public function getTotalBilledAttribute(): float
    {
        return $this->bills()->sum('amount');
    }

    /**
     * Total received across all orders for this client.
     */
    public function getTotalReceivedAttribute(): float
    {
        return $this->bills()->sum('received');
    }

    /**
     * Outstanding balance.
     */
    public function getTotalBalanceAttribute(): float
    {
        return $this->total_billed - $this->total_received;
    }
}
