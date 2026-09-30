<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('color_codes', function (Blueprint $table) {
            $table->enum('printer', ['xp600', 'i3200'])
                  ->default('xp600')
                  ->after('name');

            // Also snapshot the printer for pending_edit requests
            $table->enum('pending_printer', ['xp600', 'i3200'])
                  ->nullable()
                  ->after('pending_name');

            $table->index('printer');
        });
    }

    public function down(): void
    {
        Schema::table('color_codes', function (Blueprint $table) {
            $table->dropIndex(['printer']);
            $table->dropColumn(['printer', 'pending_printer']);
        });
    }
};
