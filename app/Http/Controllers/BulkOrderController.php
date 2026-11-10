<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStageLog;
use App\Models\ProductionSchedule;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Handles bulk operations on multiple orders from the orders index.
 * All actions are pipeline-manager only.
 */
class BulkOrderController extends Controller
{
    // Valid values for each bulk action type
    private const VALID_PRIORITIES = ['normal', 'rush', 'critical'];
    private const VALID_STATUSES   = ['pending', 'in_progress', 'on_hold', 'cancelled'];
    private const VALID_STAGES     = ['design', 'print', 'sew', 'ready', 'delivered'];
    private const MAX_IDS          = 100; // prevent accidental mass-updates

    public function __construct(private readonly SchedulingService $scheduler) {}

    /**
     * POST /orders/bulk
     * Dispatches to the appropriate sub-action based on `action` input.
     */
    public function handle(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        $validated = $request->validate([
            'ids'       => ['required', 'array', 'min:1', 'max:' . self::MAX_IDS],
            'ids.*'     => ['required', 'integer', 'exists:orders,id'],
            'action'    => ['required', 'string', 'in:delete,priority,status,stage'],
            'priority'  => ['nullable', 'string', 'in:' . implode(',', self::VALID_PRIORITIES)],
            'status'    => ['nullable', 'string', 'in:' . implode(',', self::VALID_STATUSES)],
            'stage'     => ['nullable', 'string', 'in:' . implode(',', self::VALID_STAGES)],
        ]);

        $ids = $validated['ids'];

        return match ($validated['action']) {
            'delete'   => $this->bulkDelete($ids),
            'priority' => $this->bulkPriority($ids, $validated['priority'] ?? null),
            'status'   => $this->bulkStatus($ids, $validated['status'] ?? null),
            'stage'    => $this->bulkStage($ids, $validated['stage'] ?? null),
        };
    }

    // ──────────────────────────────────────────────
    // Sub-actions
    // ──────────────────────────────────────────────

    /**
     * Soft-delete selected orders.
     */
    private function bulkDelete(array $ids): RedirectResponse
    {
        $count = Order::whereIn('id', $ids)->count();

        Order::whereIn('id', $ids)->delete();

        SchedulingService::clearScheduleCache();

        return back()->with('success', "Deleted {$count} order(s) and moved them to trash.");
    }

    /**
     * Change priority on selected orders.
     */
    private function bulkPriority(array $ids, ?string $priority): RedirectResponse
    {
        if (! $priority) {
            return back()->with('error', 'Please select a priority level.');
        }

        $count = Order::whereIn('id', $ids)->count();

        Order::whereIn('id', $ids)->update(['priority' => $priority]);

        SchedulingService::clearScheduleCache();

        return back()->with('success', "Updated priority to " . ucfirst($priority) . " for {$count} order(s).");
    }

    /**
     * Change status on selected orders.
     */
    private function bulkStatus(array $ids, ?string $status): RedirectResponse
    {
        if (! $status) {
            return back()->with('error', 'Please select a status.');
        }

        $orders = Order::whereIn('id', $ids)->get();
        $count  = $orders->count();

        DB::transaction(function () use ($orders, $status) {
            foreach ($orders as $order) {
                if ($order->status === $status) {
                    continue;
                }

                $fromStatus = $order->status;
                $order->update(['status' => $status]);

                OrderStageLog::create([
                    'order_id'    => $order->id,
                    'from_stage'  => $order->stage,
                    'to_stage'    => $order->stage,
                    'from_status' => $fromStatus,
                    'to_status'   => $status,
                    'changed_by'  => auth()->id(),
                    'notes'       => 'Bulk status update to ' . $status . '.',
                ]);
            }
        });

        SchedulingService::clearScheduleCache();

        $label = ucwords(str_replace('_', ' ', $status));

        return back()->with('success', "Set status to {$label} for {$count} order(s).");
    }

    /**
     * Move selected orders to a different stage.
     * Also logs the stage change in OrderStageLog and stamps completed_at on
     * any production_schedules rows for stages being skipped or left behind —
     * otherwise those orders remain visible in department queues even though
     * their orders.stage has already moved past that department.
     */
    private function bulkStage(array $ids, ?string $stage): RedirectResponse
    {
        if (! $stage) {
            return back()->with('error', 'Please select a stage.');
        }

        $orders = Order::whereIn('id', $ids)->get();
        $count  = $orders->count();

        // All production departments in pipeline order.
        $allDepts = ['design', 'print', 'sew'];

        // Departments whose schedule rows must be closed when moving to $stage.
        // Any department that is no longer the active stage should be stamped
        // completed_at so it disappears from that department's queue.
        //   - moving to 'ready' or 'delivered' → close all three depts
        //   - moving to 'sew'   → close design + print
        //   - moving to 'print' → close design only
        //   - moving to 'design' → close nothing (department is still active)
        $stagePosition = array_search($stage, $allDepts, true);
        $deptsToClose  = $stagePosition === false
            ? $allDepts  // ready/delivered — close everything
            : array_slice($allDepts, 0, $stagePosition); // close depts before target

        DB::transaction(function () use ($orders, $stage, $deptsToClose) {
            foreach ($orders as $order) {
                if ($order->stage === $stage) {
                    continue;
                }

                $fromStage = $order->stage;
                $newStatus = $stage === 'delivered' ? 'completed'
                    : ($stage === 'ready'           ? 'completed'
                    :                                 $order->status);

                $order->update(['stage' => $stage, 'status' => $newStatus]);

                // Stamp completed_at on every production_schedule row for
                // departments the order is being moved past. This ensures
                // those department dashboards stop showing the order.
                if (! empty($deptsToClose)) {
                    ProductionSchedule::where('order_id', $order->id)
                        ->whereIn('department', $deptsToClose)
                        ->whereNull('completed_at')
                        ->update([
                            'completed_at' => now(),
                            'completed_by' => auth()->id(),
                        ]);
                }

                // Also close the target stage's schedule row if moving to
                // ready/delivered, since the order is fully done.
                if (in_array($stage, ['ready', 'delivered'], true)) {
                    ProductionSchedule::where('order_id', $order->id)
                        ->whereNull('completed_at')
                        ->update([
                            'completed_at' => now(),
                            'completed_by' => auth()->id(),
                        ]);
                }

                OrderStageLog::create([
                    'order_id'    => $order->id,
                    'from_stage'  => $fromStage,
                    'to_stage'    => $stage,
                    'from_status' => $order->getOriginal('status'),
                    'to_status'   => $newStatus,
                    'changed_by'  => auth()->id(),
                    'notes'       => 'Bulk stage move to ' . $stage . '.',
                ]);
            }
        });

        SchedulingService::clearScheduleCache();

        $label = ucfirst($stage === 'ready' ? 'Ready for Delivery' : $stage);

        return back()->with('success', "Moved {$count} order(s) to {$label} stage.");
    }
}
