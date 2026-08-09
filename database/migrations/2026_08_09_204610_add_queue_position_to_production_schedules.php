<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_schedules', function (Blueprint $table) {
            // PM-set manual position. NULL = auto-sorted by scheduler.
            // Lower number = higher in queue. Persists across days.
            $table->unsignedSmallInteger('queue_position')->nullable()->after('is_overtime');
        });
    }

    public function down(): void
    {
        Schema::table('production_schedules', function (Blueprint $table) {
            $table->dropColumn('queue_position');
        });
    }
};
