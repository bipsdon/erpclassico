<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->cascadeOnDelete();

            // Original filename as uploaded by user
            $table->string('original_name', 255);

            // UUID-based name to prevent collisions and path traversal
            $table->string('stored_name', 255);

            // Relative path under storage/app/private
            $table->string('file_path', 500);

            // e.g. application/pdf, image/jpeg, image/png, application/zip
            $table->string('mime_type', 100);

            // File size in bytes
            $table->unsignedInteger('file_size');

            $table->foreignId('uploaded_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();

            $table->index('order_id');
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_attachments');
    }
};
