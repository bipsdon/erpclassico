<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN on enums.
        // We rebuild by renaming, recreating, copying, then dropping old table.
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: drop and recreate with the new enum (data is seeded, not production-critical)
            Schema::table('pipeline_notifications', function (Blueprint $table) {
                // Add reply_to_id nullable self-referencing FK
                $table->unsignedBigInteger('reply_to_id')->nullable()->after('id');
                $table->foreign('reply_to_id')
                      ->references('id')
                      ->on('pipeline_notifications')
                      ->nullOnDelete();
            });

            // SQLite: widen the enum by replacing the column via raw SQL
            // (SQLite stores enum as TEXT — just drop the CHECK constraint by recreating)
            DB::statement('
                CREATE TABLE pipeline_notifications_new AS
                SELECT * FROM pipeline_notifications
            ');
            Schema::drop('pipeline_notifications');
            DB::statement('
                CREATE TABLE pipeline_notifications (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    reply_to_id INTEGER REFERENCES pipeline_notifications_new(id) ON DELETE SET NULL,
                    sent_by INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    target_department TEXT CHECK(target_department IN (
                        \'designer\',\'printing_manager\',\'sewing_manager\',\'pipeline_manager\'
                    )),
                    subject VARCHAR(200) NOT NULL,
                    message TEXT NOT NULL,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
            DB::statement('INSERT INTO pipeline_notifications SELECT * FROM pipeline_notifications_new');
            Schema::drop('pipeline_notifications_new');
        } else {
            // MySQL / PostgreSQL path
            Schema::table('pipeline_notifications', function (Blueprint $table) {
                $table->unsignedBigInteger('reply_to_id')->nullable()->after('id');
                $table->foreign('reply_to_id')
                      ->references('id')
                      ->on('pipeline_notifications')
                      ->nullOnDelete();
            });

            DB::statement("
                ALTER TABLE pipeline_notifications
                MODIFY COLUMN target_department
                ENUM('designer','printing_manager','sewing_manager','pipeline_manager') NULL
            ");
        }
    }

    public function down(): void
    {
        Schema::table('pipeline_notifications', function (Blueprint $table) {
            $table->dropForeign(['reply_to_id']);
            $table->dropColumn('reply_to_id');
        });
    }
};
