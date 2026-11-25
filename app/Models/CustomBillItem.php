<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomBillItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'custom_bill_id',
        'title',
        'description',
        'qty',
        'price',
        'total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty'   => 'float',
            'price' => 'float',
            'total' => 'float',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(CustomBill::class, 'custom_bill_id');
    }
}
