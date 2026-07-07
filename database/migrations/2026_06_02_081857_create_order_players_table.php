<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_players', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->cascadeOnDelete();

            $table->string('player_name', 100);

            // Stored as string to support "00", "0A", custom numbers
            $table->string('jersey_number', 10);

            $table->enum('size', ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'])->nullable();

            $table->string('notes', 255)->nullable();

            // Allows reordering within an order (drag-and-drop later)
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('order_id');
            $table->index(['order_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_players');
    }
};
