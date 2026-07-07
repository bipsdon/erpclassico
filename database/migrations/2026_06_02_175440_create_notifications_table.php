<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            // null = broadcast to all departments
            $table->enum('target_department', ['designer', 'printing_manager', 'sewing_manager'])->nullable();
            $table->string('subject', 200);
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('pipeline_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')
                  ->constrained('pipeline_notifications')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['notification_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_notification_reads');
        Schema::dropIfExists('pipeline_notifications');
    }
};
