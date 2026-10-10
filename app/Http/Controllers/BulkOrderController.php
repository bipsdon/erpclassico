<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStageLog;
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
     * Also logs the stage change in OrderStageLog.
     */
    private function bulkStage(array $ids, ?string $stage): RedirectResponse
    {
        if (! $stage) {
            return back()->with('error', 'Please select a stage.');
        }

        $orders = Order::whereIn('id', $ids)->get();
        $count  = $orders->count();

        DB::transaction(function () use ($orders, $stage) {
            foreach ($orders as $order) {
                if ($order->stage === $stage) {
                    continue;
                }

                $fromStage = $order->stage;
                $newStatus = $stage === 'delivered' ? 'completed'
                    : ($stage === 'ready'           ? 'completed'
                    :                                 $order->status);

                $order->update(['stage' => $stage, 'status' => $newStatus]);

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
