<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number', 30)->unique();
            $table->string('customer_name', 150);
            $table->string('customer_phone', 30);
            $table->unsignedSmallInteger('quantity');

            $table->date('order_date');
            $table->date('delivery_date');

            $table->enum('priority', ['normal', 'rush', 'critical'])->default('normal');

            $table->enum('stage', [
                'design',
                'print',
                'sew',
                'ready',
                'delivered',
            ])->default('design');

            $table->enum('status', [
                'pending',
                'in_progress',
                'completed',
                'on_hold',
                'cancelled',
            ])->default('pending');

            $table->longText('details')->nullable();   // Quill rich-text HTML
            $table->text('notes')->nullable();          // Internal pipeline notes

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Query performance indexes
            $table->index('delivery_date');
            $table->index('stage');
            $table->index('priority');
            $table->index('status');
            $table->index('created_by');
            $table->index(['stage', 'status']);
            $table->index(['delivery_date', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
