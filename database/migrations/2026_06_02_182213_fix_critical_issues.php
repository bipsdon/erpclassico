<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix critical issues:
 *
 * 1. Add 'pipeline_manager' to pipeline_notifications.target_department enum
 *    so ready→PM notifications don't broadcast to all departments.
 *
 * 2. Add missing indexes on production_schedules (department + scheduled_date)
 *    and pipeline_notifications (target_department) that are queried on every
 *    dashboard page load.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Widen target_department enum ──────────────────────────────────
        // SQLite (used in dev/test) doesn't support ALTER COLUMN for enums,
        // so we check the driver and handle both cases.
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: recreate the column by rebuilding the table isn't practical,
            // but SQLite ignores enum constraints anyway — no change needed.
            // The application-layer validation in the controller is the guard.
        } else {
            DB::statement("
                ALTER TABLE pipeline_notifications
                MODIFY COLUMN target_department
                ENUM('designer','printing_manager','sewing_manager','pipeline_manager')
                NULL
            ");
        }

        // ── 2. Missing indexes ────────────────────────────────────────────────
        Schema::table('production_schedules', function (Blueprint $table) {
            // Core scheduling query: WHERE department = ? AND scheduled_date = ?
            if (! $this->hasIndex('production_schedules', 'production_schedules_dept_date_index')) {
                $table->index(['department', 'scheduled_date'], 'production_schedules_dept_date_index');
            }
            // Rebuild query: WHERE order_id = ? AND department = ?
            if (! $this->hasIndex('production_schedules', 'production_schedules_order_dept_index')) {
                $table->index(['order_id', 'department'], 'production_schedules_order_dept_index');
            }
        });

        Schema::table('pipeline_notifications', function (Blueprint $table) {
            // Badge poll: WHERE target_department = ? (or IS NULL)
            if (! $this->hasIndex('pipeline_notifications', 'pipeline_notifications_target_dept_index')) {
                $table->index('target_department', 'pipeline_notifications_target_dept_index');
            }
            // Inbox query ordered by created_at DESC
            if (! $this->hasIndex('pipeline_notifications', 'pipeline_notifications_created_at_index')) {
                $table->index('created_at', 'pipeline_notifications_created_at_index');
            }
        });

        Schema::table('pipeline_notification_reads', function (Blueprint $table) {
            // Unread count: WHERE user_id = ? AND notification_id IN (...)
            if (! $this->hasIndex('pipeline_notification_reads', 'notif_reads_user_notif_index')) {
                $table->index(['user_id', 'notification_id'], 'notif_reads_user_notif_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_schedules', function (Blueprint $table) {
            $table->dropIndex('production_schedules_dept_date_index');
            $table->dropIndex('production_schedules_order_dept_index');
        });

        Schema::table('pipeline_notifications', function (Blueprint $table) {
            $table->dropIndex('pipeline_notifications_target_dept_index');
            $table->dropIndex('pipeline_notifications_created_at_index');
        });

        Schema::table('pipeline_notification_reads', function (Blueprint $table) {
            $table->dropIndex('notif_reads_user_notif_index');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $idx) {
                if ($idx->name === $indexName) {
                    return true;
                }
            }
            return false;
        } catch (\Throwable) {
            return false;
        }
    }
};
