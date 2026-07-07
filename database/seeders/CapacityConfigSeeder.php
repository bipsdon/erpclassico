<?php

namespace Database\Seeders;

use App\Models\CapacityConfig;
use App\Models\User;
use Illuminate\Database\Seeder;

class CapacityConfigSeeder extends Seeder
{
    /**
     * Seed per-product-type capacity rates for printing and sewing.
     *
     * Each row = "department can produce N units/day of product_type at full capacity".
     *
     * Rates are based on a single full working day with a full crew.
     * Mixing product types on the same day is handled by the scheduler,
     * which sums (quantity / units_per_day) fractions to get total workload.
     */
    public function run(): void
    {
        $manager = User::where('role', 'pipeline_manager')->firstOrFail();

        $configs = [
            // ── Printing ─────────────────────────────────────────────
            // High-speed sublimation / screen print
            ['department' => 'print', 'product_type' => 'jersey',     'units_per_day' => 300, 'label' => 'Jersey (Printing)'],
            ['department' => 'print', 'product_type' => 'tracksuit',  'units_per_day' => 200, 'label' => 'Tracksuit (Printing)'],
            ['department' => 'print', 'product_type' => 'polo_shirt', 'units_per_day' => 250, 'label' => 'Polo Shirt (Printing)'],
            ['department' => 'print', 'product_type' => 'shorts',     'units_per_day' => 350, 'label' => 'Shorts (Printing)'],
            ['department' => 'print', 'product_type' => 'other',      'units_per_day' => 200, 'label' => 'Other (Printing)'],

            // ── Sewing ────────────────────────────────────────────────
            // Jerseys are simpler cuts; tracksuits have more panels + zips
            ['department' => 'sew', 'product_type' => 'jersey',     'units_per_day' => 80,  'label' => 'Jersey (Sewing)'],
            ['department' => 'sew', 'product_type' => 'tracksuit',  'units_per_day' => 55,  'label' => 'Tracksuit (Sewing)'],
            ['department' => 'sew', 'product_type' => 'polo_shirt', 'units_per_day' => 75,  'label' => 'Polo Shirt (Sewing)'],
            ['department' => 'sew', 'product_type' => 'shorts',     'units_per_day' => 100, 'label' => 'Shorts (Sewing)'],
            ['department' => 'sew', 'product_type' => 'other',      'units_per_day' => 60,  'label' => 'Other (Sewing)'],
        ];

        foreach ($configs as $config) {
            CapacityConfig::updateOrCreate(
                ['department' => $config['department'], 'product_type' => $config['product_type']],
                [
                    'units_per_day' => $config['units_per_day'],
                    'label'         => $config['label'],
                    'updated_by'    => $manager->id,
                    'updated_at'    => now(),
                ]
            );
        }
    }
}
