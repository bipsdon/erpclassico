<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redesign capacity_configs from a single number per department
 * to a per-product-type rate per department.
 *
 * New model: each row says "department X can produce N units/day of product_type Y".
 * The scheduler converts quantity → fraction-of-day using these rates, then sums
 * across all product types to get the total workload as a percentage of capacity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::drop('capacity_configs');

        Schema::create('capacity_configs', function (Blueprint $table) {
            $table->id();

            // Which department this rate applies to (print and sew are capacity-bound; design is unlimited)
            $table->string('department', 20);

            // Which product type this rate is for
            $table->string('product_type', 30);

            // How many units of this product type can be completed per full working day
            // e.g. jersey→sewing = 80, tracksuit→sewing = 55, polo_shirt→sewing = 75
            $table->unsignedSmallInteger('units_per_day');

            // Human-readable label shown on the dashboard
            $table->string('label', 80)->nullable();

            $table->foreignId('updated_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamp('updated_at')->nullable();

            // One rate per department+product_type combination
            $table->unique(['department', 'product_type']);
            $table->index('department');
            $table->index('product_type');
        });
    }

    public function down(): void
    {
        Schema::drop('capacity_configs');

        Schema::create('capacity_configs', function (Blueprint $table) {
            $table->id();
            $table->enum('department', ['print', 'sew'])->unique();
            $table->unsignedSmallInteger('daily_capacity');
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }
};
