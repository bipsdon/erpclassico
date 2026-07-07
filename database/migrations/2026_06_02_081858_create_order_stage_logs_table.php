<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_stage_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->cascadeOnDelete();

            $table->string('from_stage', 30)->nullable();
            $table->string('to_stage', 30);

            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);

            $table->foreignId('changed_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->text('notes')->nullable();

            // Audit log — no updated_at needed
            $table->timestamp('created_at')->useCurrent();

            $table->index('order_id');
            $table->index('changed_by');
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_stage_logs');
    }
};
