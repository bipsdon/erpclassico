<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CustomBill extends Model
{
    protected $fillable = [
        'invoice_number',
        'bill_to',
        'bill_date',
        'subtotal',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'subtotal'  => 'float',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(CustomBillItem::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ──────────────────────────────────────────────
    // Auto invoice number: INV-YYYYMM-XXXX
    // ──────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (CustomBill $bill) {
            if (empty($bill->invoice_number)) {
                $prefix = 'INV-' . now()->format('Ym') . '-';

                DB::transaction(function () use ($bill, $prefix) {
                    $last = static::where('invoice_number', 'like', $prefix . '%')
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->value('invoice_number');

                    $sequence = $last
                        ? ((int) substr($last, -4)) + 1
                        : 1;

                    $bill->invoice_number = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
                });
            }
        });
    }
}
