<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('color_codes', function (Blueprint $table) {
            $table->id();

            // ── Core color data ───────────────────────────────────────
            $table->string('name', 100);
            $table->unsignedTinyInteger('cyan');      // C  0–100
            $table->unsignedTinyInteger('magenta');   // M  0–100
            $table->unsignedTinyInteger('yellow');    // Y  0–100
            $table->unsignedTinyInteger('black');     // K  0–100

            // ── Approval workflow ─────────────────────────────────────
            // status: active | pending_add | pending_edit | pending_delete
            $table->enum('status', [
                'active',
                'pending_add',
                'pending_edit',
                'pending_delete',
            ])->default('active');

            // Who created / last touched the record
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // When a designer submits an edit/delete request we snapshot the
            // proposed new values here so the PM can compare before approving.
            $table->string('pending_name', 100)->nullable();
            $table->unsignedTinyInteger('pending_cyan')->nullable();
            $table->unsignedTinyInteger('pending_magenta')->nullable();
            $table->unsignedTinyInteger('pending_yellow')->nullable();
            $table->unsignedTinyInteger('pending_black')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('color_codes');
    }
};
