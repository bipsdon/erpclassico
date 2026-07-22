<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Nullable URL to a Facebook-hosted photo; no files are stored locally.
            $table->string('profile_picture_url', 2048)
                  ->nullable()
                  ->after('whatsapp_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('profile_picture_url');
        });
    }
};
