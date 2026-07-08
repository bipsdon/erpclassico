<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // JSON array of stages this order must pass through.
            // e.g. ["design","print","sew"] | ["design","print"] | ["design"]
            // Placed after 'stage' column for logical grouping.
            $table->json('pipeline')->nullable()->after('stage');
        });

        // Back-fill existing orders with the full pipeline so behaviour is unchanged.
        DB::table('orders')->whereNull('pipeline')->update([
            'pipeline' => json_encode(['design', 'print', 'sew']),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('pipeline');
        });
    }
};
