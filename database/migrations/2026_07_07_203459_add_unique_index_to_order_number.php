<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Index may already exist from an earlier migration — skip if so.
        if (! \Illuminate\Support\Facades\Schema::hasIndex('orders', 'orders_order_number_unique')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique('order_number');
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['order_number']);
        });
    }
};
