<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->cascadeOnDelete();

            $table->enum('department', ['design', 'print', 'sew']);

            // The calendar date this order is slotted for this department
            $table->date('scheduled_date');

            // Supports partial scheduling (overflow across days)
            $table->unsignedSmallInteger('quantity_scheduled');

            // True when this slot pushes the day's total over capacity
            $table->boolean('is_overtime')->default(false);

            // Filled when the department marks the slot done
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('completed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            // An order can only have one schedule slot per department
            $table->unique(['order_id', 'department']);

            $table->index('department');
            $table->index('scheduled_date');
            $table->index(['department', 'scheduled_date']);
            $table->index(['department', 'scheduled_date', 'completed_at'], 'ps_dept_date_completed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_schedules');
    }
};
