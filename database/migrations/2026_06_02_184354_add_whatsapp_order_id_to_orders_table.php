<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // WhatsApp order reference — nullable, free-text (e.g. "WA-2024-001" or a chat message ID)
            $table->string('whatsapp_order_id', 100)
                  ->nullable()
                  ->after('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('whatsapp_order_id');
        });
    }
};
