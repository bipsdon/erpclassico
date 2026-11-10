<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data-fix migration.
 *
 * Bug: BulkOrderController::bulkStage() moved orders.stage forward without
 * stamping completed_at on the corresponding production_schedules rows.
 * Department queues filter on `completed_at IS NULL`, so those orders kept
 * appearing in designer / printing / sewing queues even after being bulk-moved
 * past those stages.
 *
 * Fix: stamp completed_at = NOW() on every production_schedules row where
 * the order's current stage has already passed that department.
 *
 * Mapping:
 *   order.stage = print | sew | ready | delivered  →  close department = design
 *   order.stage = sew   | ready | delivered        →  close department = print
 *   order.stage = ready | delivered                →  close department = sew
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::statement("
            UPDATE production_schedules ps
            JOIN  orders o ON o.id = ps.order_id
            SET   ps.completed_at = ?,
                  ps.completed_by = NULL
            WHERE o.deleted_at    IS NULL
              AND ps.completed_at IS NULL
              AND (
                    (o.stage IN ('print','sew','ready','delivered') AND ps.department = 'design')
                 OR (o.stage IN ('sew','ready','delivered')         AND ps.department = 'print')
                 OR (o.stage IN ('ready','delivered')               AND ps.department = 'sew')
              )
        ", [$now]);
    }

    public function down(): void
    {
        // Not reversible — we cannot know which completed_at values were set
        // by this migration vs. by legitimate stage completions.
    }
};
