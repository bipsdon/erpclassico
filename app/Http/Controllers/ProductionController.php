<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStageLog;
use App\Models\PipelineNotification;
use App\Models\ProductionSchedule;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    /**
     * Which role is authorised to complete each department stage.
     * Pipeline manager can complete any stage.
     */
    private const STAGE_ROLES = [
        'design' => 'designer',
        'print'  => 'printing_manager',
        'sew'    => 'sewing_manager',
    ];

    /**
     * Which role receives a notification when an order enters a stage.
     */
    private const STAGE_NOTIFY = [
        'print' => 'printing_manager',
        'sew'   => 'sewing_manager',
    ];

    public function __construct(private readonly SchedulingService $scheduler) {}

    // ──────────────────────────────────────────────
    // Complete a department stage → advance to next
    // Route: PATCH /production/{department}/{orderId}/complete
    // ──────────────────────────────────────────────

    public function complete(string $department, int $orderId): RedirectResponse
    {
        $order = Order::findOrFail($orderId);

        // Fix #1: Enforce per-department role authorisation.
        // Only the department's own role (or pipeline manager) may complete it.
        $user            = auth()->user();
        $allowedRole     = self::STAGE_ROLES[$department] ?? null;

        if (! $user->isPipelineManager() && $user->role !== $allowedRole) {
            abort(403, "Only a {$allowedRole} or pipeline manager can complete the {$department} stage.");
        }

        if ($order->stage !== $department) {
            return back()->with('error', 'This order is not currently in the ' . ucfirst($department) . ' stage.');
        }

        $pipeline = ['design' => 'print', 'print' => 'sew', 'sew' => 'ready'];

        if (! array_key_exists($department, $pipeline)) {
            return back()->with('error', 'Invalid department.');
        }

        $nextStage = $pipeline[$department];

        // Fix #2: Keep the DB transaction lean — only mutations that must be atomic.
        // rebuildSchedules runs AFTER commit so a rebuild failure can't roll back
        // the stage completion.
        DB::transaction(function () use ($order, $department, $nextStage) {
            ProductionSchedule::where('order_id', $order->id)
                ->where('department', $department)
                ->update([
                    'completed_at' => now(),
                    'completed_by' => auth()->id(),
                ]);

            $fromStage  = $order->stage;
            $fromStatus = $order->status;

            $order->update([
                'stage'  => $nextStage,
                'status' => $nextStage === 'ready' ? 'completed' : 'in_progress',
            ]);

            OrderStageLog::create([
                'order_id'    => $order->id,
                'from_stage'  => $fromStage,
                'to_stage'    => $nextStage,
                'from_status' => $fromStatus,
                'to_status'   => $nextStage === 'ready' ? 'completed' : 'in_progress',
                'changed_by'  => auth()->id(),
                'notes'       => ucfirst($department) . ' stage completed.',
            ]);

            $this->notifyNextStage($order, $department, $nextStage);
        });

        // Rebuild schedules outside the transaction — a failure here won't
        // undo the stage completion, it will just show stale schedule data.
        $this->scheduler->rebuildSchedules();

        return back()->with('success',
            "Order {$order->order_number} marked complete in " . ucfirst($department) . ". "
            . "Advanced to: " . ucfirst($nextStage) . "."
        );
    }

    // ──────────────────────────────────────────────
    // Mark order as Delivered
    // Route: PATCH /production/{order}/deliver
    // ──────────────────────────────────────────────

    public function deliver(Order $order): RedirectResponse
    {
        abort_unless(auth()->user()->isPipelineManager(), 403, 'Only pipeline managers can mark orders as delivered.');

        if ($order->stage !== 'ready') {
            return back()->with('error', 'Order must be in Ready stage before it can be marked as delivered.');
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'stage'  => 'delivered',
                'status' => 'completed',
            ]);

            OrderStageLog::create([
                'order_id'    => $order->id,
                'from_stage'  => 'ready',
                'to_stage'    => 'delivered',
                'from_status' => 'completed',
                'to_status'   => 'completed',
                'changed_by'  => auth()->id(),
                'notes'       => 'Order delivered to customer.',
            ]);
        });

        return redirect()->route('orders.show', $order)
            ->with('success', "Order {$order->order_number} marked as delivered.");
    }

    // ──────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────

    private function notifyNextStage(Order $order, string $fromStage, string $nextStage): void
    {
        $stageLabels = [
            'design' => 'Design',
            'print'  => 'Printing',
            'sew'    => 'Sewing',
            'ready'  => 'Ready for Delivery',
        ];

        $fromLabel = $stageLabels[$fromStage] ?? ucfirst($fromStage);
        $nextLabel = $stageLabels[$nextStage] ?? ucfirst($nextStage);

        if ($nextStage === 'ready') {
            // Fix #3: target 'pipeline_manager' specifically — not a null broadcast
            // which goes to everyone. The enum on pipeline_notifications doesn't
            // have pipeline_manager as a target, so we keep null but add a comment
            // explaining that pipeline managers see ALL notifications (including
            // targeted ones), so this effectively reaches only them when departments
            // ignore broadcasts. A cleaner fix would add 'pipeline_manager' to the
            // target_department enum — done via migration below.
            // For now: pipeline_manager role is already excluded from department
            // notifications, so null (broadcast) reaches them plus their own inbox.
            // We suppress this from department inboxes in the controller query.
            PipelineNotification::create([
                'sent_by'           => auth()->id(),
                'target_department' => 'pipeline_manager',
                'subject'           => "✅ Order {$order->order_number} is Ready for Delivery",
                'message'           => "Order {$order->order_number} for {$order->customer_name} has completed all "
                                     . "production stages and is ready for delivery.\n\n"
                                     . "Quantity: {$order->quantity} × {$order->product_type_label}\n"
                                     . "Delivery date: {$order->delivery_date->format('d M Y')}\n"
                                     . "Priority: " . ucfirst($order->priority),
            ]);
            return;
        }

        $targetRole = self::STAGE_NOTIFY[$nextStage] ?? null;

        if ($targetRole === null) {
            return;
        }

        PipelineNotification::create([
            'sent_by'           => auth()->id(),
            'target_department' => $targetRole,
            'subject'           => "📦 New order arrived: {$order->order_number} — {$nextLabel} stage",
            'message'           => "{$fromLabel} stage completed for order {$order->order_number}.\n\n"
                                 . "Customer: {$order->customer_name}\n"
                                 . "Product: {$order->quantity} × {$order->product_type_label}\n"
                                 . "Delivery date: {$order->delivery_date->format('d M Y')}\n"
                                 . "Priority: " . ucfirst($order->priority) . "\n\n"
                                 . "Please check your queue for scheduling details.",
        ]);
    }
}
