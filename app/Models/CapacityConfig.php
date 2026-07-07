<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class CapacityConfig extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'department',
        'product_type',
        'units_per_day',
        'label',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'units_per_day' => 'integer',
            'updated_at'    => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Static helpers
    // ──────────────────────────────────────────────

    /**
     * All supported product types with their display labels.
     * Extend this list when new product types are added.
     */
    public static function productTypes(): array
    {
        return [
            'jersey'     => 'Jersey',
            'tracksuit'  => 'Tracksuit',
            'polo_shirt' => 'Polo Shirt',
            'shorts'     => 'Shorts',
            'other'      => 'Other',
        ];
    }

    /**
     * Default capacity rates (units/day) used when DB has no config yet.
     * Keyed as ['department.product_type' => units_per_day].
     */
    public static function defaults(): array
    {
        return [
            // Printing department
            'print.jersey'     => 300,
            'print.tracksuit'  => 200,
            'print.polo_shirt' => 250,
            'print.shorts'     => 350,
            'print.other'      => 200,

            // Sewing department
            'sew.jersey'       => 80,
            'sew.tracksuit'    => 55,
            'sew.polo_shirt'   => 75,
            'sew.shorts'       => 100,
            'sew.other'        => 60,
        ];
    }

    /**
     * Load all rates for a department, keyed by product_type.
     * Falls back to hardcoded defaults for any missing product type.
     *
     * @return array<string, int>  ['jersey' => 80, 'tracksuit' => 55, ...]
     */
    public static function ratesFor(string $department): array
    {
        $rows = static::where('department', $department)
            ->get()
            ->keyBy('product_type')
            ->map(fn ($r) => $r->units_per_day)
            ->all();

        // Fill in any missing product types with defaults
        $defaults = static::defaults();
        foreach (array_keys(static::productTypes()) as $type) {
            if (! isset($rows[$type])) {
                $rows[$type] = $defaults["{$department}.{$type}"] ?? 60;
            }
        }

        return $rows;
    }

    /**
     * Get the units/day for a specific department + product type.
     */
    public static function rateFor(string $department, string $productType): int
    {
        $row = static::where('department', $department)
            ->where('product_type', $productType)
            ->first();

        if ($row) {
            return $row->units_per_day;
        }

        return static::defaults()["{$department}.{$productType}"] ?? 60;
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
