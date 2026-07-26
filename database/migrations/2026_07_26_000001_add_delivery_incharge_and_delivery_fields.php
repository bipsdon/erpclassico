<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Add 'delivery_incharge' to users.role enum.
 * 2. Add delivery fields to orders:
 *    - delivery_method   (dropdown: pathao|company_delivery|bus_ma_haldine|customer_pickup|ncm)
 *    - delivery_details  (multiline text, set by pipeline manager before handover)
 *    - challan_number    (required when delivery incharge marks delivered)
 *    - delivered_by      (FK to users — who actually clicked "Mark Delivered")
 * 3. Add 'delivery_incharge' to pipeline_notifications.target_department enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Widen users.role enum ──────────────────────────────────────────
        if (DB::getDriverName() === 'sqlite') {
            // SQLite stores enums as TEXT — constraint is advisory only.
            // No DDL change needed; the app-layer validation is the guard.
        } else {
            DB::statement("
                ALTER TABLE users
                MODIFY COLUMN role
                ENUM('pipeline_manager','designer','printing_manager','sewing_manager','delivery_incharge')
                NOT NULL DEFAULT 'designer'
            ");
        }

        // ── 2. Add delivery fields to orders ─────────────────────────────────
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_method', 30)->nullable()->after('delivery_date');
            $table->text('delivery_details')->nullable()->after('delivery_method');
            $table->string('challan_number', 100)->nullable()->after('delivery_details');
            $table->foreignId('delivered_by')->nullable()
                  ->after('challan_number')
                  ->constrained('users')
                  ->nullOnDelete();
        });

        // ── 3. Widen pipeline_notifications.target_department enum ───────────
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: rebuild the table to update the CHECK constraint.
            DB::statement('
                CREATE TABLE pipeline_notifications_new AS
                SELECT * FROM pipeline_notifications
            ');
            Schema::drop('pipeline_notifications');
            DB::statement("
                CREATE TABLE pipeline_notifications (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    reply_to_id INTEGER REFERENCES pipeline_notifications(id) ON DELETE SET NULL,
                    sent_by INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    target_department TEXT CHECK(target_department IN (
                        'designer','printing_manager','sewing_manager',
                        'pipeline_manager','delivery_incharge'
                    )),
                    subject VARCHAR(200) NOT NULL,
                    message TEXT NOT NULL,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ");
            DB::statement('INSERT INTO pipeline_notifications SELECT * FROM pipeline_notifications_new');
            Schema::drop('pipeline_notifications_new');
        } else {
            DB::statement("
                ALTER TABLE pipeline_notifications
                MODIFY COLUMN target_department
                ENUM('designer','printing_manager','sewing_manager','pipeline_manager','delivery_incharge')
                NULL
            ");
        }
    }

    public function down(): void
    {
        // Remove delivery fields
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['delivered_by']);
            $table->dropColumn(['delivery_method', 'delivery_details', 'challan_number', 'delivered_by']);
        });

        // Narrow role enum back (MySQL only)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE users
                MODIFY COLUMN role
                ENUM('pipeline_manager','designer','printing_manager','sewing_manager')
                NOT NULL DEFAULT 'designer'
            ");

            DB::statement("
                ALTER TABLE pipeline_notifications
                MODIFY COLUMN target_department
                ENUM('designer','printing_manager','sewing_manager','pipeline_manager')
                NULL
            ");
        }
    }
};
