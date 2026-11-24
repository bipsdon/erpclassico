<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPayment extends Model
{
    protected $fillable = [
        'client_id',
        'amount',
        'payment_date',
        'method',
        'reference',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'float',
            'payment_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'cash'          => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'esewa'         => 'eSewa',
            'khalti'        => 'Khalti',
            'cheque'        => 'Cheque',
            default         => $this->method ? ucfirst($this->method) : '—',
        };
    }
}
