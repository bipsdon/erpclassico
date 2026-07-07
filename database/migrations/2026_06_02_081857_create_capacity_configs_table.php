<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capacity_configs', function (Blueprint $table) {
            $table->id();

            // Only print and sew have a capacity ceiling; design is unlimited
            $table->enum('department', ['print', 'sew'])->unique();

            $table->unsignedSmallInteger('daily_capacity');

            $table->foreignId('updated_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            // Only updated_at — records are seeded once, then edited in-place
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capacity_configs');
    }
};
