<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\CapacityConfig;
use App\Models\Order;
use App\Models\ProductionSchedule;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class GanttController extends Controller
{
    /**
     * Rolling window length in days (today + 13 = 14 days total).
     */
    private const WINDOW_DAYS = 14;

    public function index(): View
    {
        $user  = auth()->user();
        $today = now()->startOfDay();

        // Build date range: today through today + 13
        $dates = collect(range(0, self::WINDOW_DAYS - 1))
            ->map(fn (int $i) => $today->copy()->addDays($i)->toDateString())
            ->all();

        if ($user->isPipelineManager()) {
            return $this->pmGantt($dates, $today);
        }

        // Designer view (scoped to design stage orders only)
        return $this->designerGantt($dates, $today);
    }

    // ──────────────────────────────────────────────────────────────
    // PM: cross-department schedule Gantt
    // ──────────────────────────────────────────────────────────────

    private function pmGantt(array $dates, Carbon $today): View
    {
        $windowEnd = $today->copy()->addDays(self::WINDOW_DAYS - 1)->toDateString();

        // Load all active (non-cancelled, non-delivered) orders that have at
        // least one production_schedule slot within the window.
        $schedules = ProductionSchedule::query()
            ->whereBetween('scheduled_date', [$today->toDateString(), $windowEnd])
            ->whereNull('completed_at')
            ->with(['order'])
            ->get()
            ->filter(fn ($s) => $s->order && ! in_array($s->order->status, ['cancelled']))
            ->filter(fn ($s) => $s->order->stage !== 'delivered');

        // Group by department → order_id so we get one bar per order per dept.
        // Structure: ['design' => [orderId => barData], 'print' => [...], 'sew' => [...]]
        $departments = ['design', 'print', 'sew'];
        $rows        = [];

        foreach ($departments as $dept) {
            $deptSchedules = $schedules->filter(fn ($s) => $s->department === $dept);

            foreach ($deptSchedules as $slot) {
                $order    = $slot->order;
                $rate     = CapacityConfig::rateFor($dept, $order->product_type);
                $daysSpan = $rate > 0 ? (int) ceil($order->quantity / $rate) : 1;
                $daysSpan = max(1, $daysSpan);

                $startDate    = Carbon::parse($slot->scheduled_date)->toDateString();
                $deliveryDate = $order->delivery_date->toDateString();
                $daysLeft     = (int) $today->diffInDays(Carbon::parse($deliveryDate), false);

                $rows[$dept][$order->id] = [
                    'orderId'         => $order->id,
                    'ref'             => $order->whatsapp_order_id ?? $order->order_number,
                    'orderNumber'     => $order->order_number,
                    'customerName'    => $order->customer_name,
                    'quantity'        => $order->quantity,
                    'productType'     => $order->product_type_label,
                    'priority'        => $order->priority,
                    'stage'           => $order->stage,
                    'isOvertime'      => $slot->is_overtime,
                    'scheduledDate'   => $startDate,
                    'deliveryDate'    => $deliveryDate,
                    'daysSpan'        => $daysSpan,
                    'daysLeft'        => $daysLeft,
                    'startOffset'     => max(0, (int) $today->diffInDays(Carbon::parse($startDate), false)),
                    'deliveryOffset'  => (int) $today->diffInDays(Carbon::parse($deliveryDate), false),
                ];
            }
        }

        // Sort each department's rows by priority then delivery date
        foreach ($departments as $dept) {
            if (! isset($rows[$dept])) {
                $rows[$dept] = [];
                continue;
            }
            uasort($rows[$dept], function ($a, $b) {
                $rank = ['critical' => 0, 'rush' => 1, 'normal' => 2];
                $diff = ($rank[$a['priority']] ?? 9) - ($rank[$b['priority']] ?? 9);
                return $diff !== 0 ? $diff : strcmp($a['deliveryDate'], $b['deliveryDate']);
            });
        }

        return view('schedule.gantt-pm', compact('dates', 'rows', 'departments', 'today'));
    }

    // ──────────────────────────────────────────────────────────────
    // Designer: deadline-visualisation Gantt
    // Orders shown as "available time" bars from today → delivery date
    // ──────────────────────────────────────────────────────────────

    private function designerGantt(array $dates, Carbon $today): View
    {
        $windowEnd = $today->copy()->addDays(self::WINDOW_DAYS - 1)->toDateString();

        // All pending design-stage orders whose delivery date is within the
        // window (or already overdue — show those too, clamped to day 0).
        $orders = Order::query()
            ->where('stage', 'design')
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'rush' THEN 1 ELSE 2 END")
            ->orderBy('delivery_date')
            ->get();

        $rows = $orders->map(function (Order $order) use ($today, $windowEnd) {
            $deliveryDate   = $order->delivery_date->toDateString();
            $daysLeft       = (int) $today->diffInDays(Carbon::parse($deliveryDate), false);

            // Bar spans from today (or 0 if overdue) to delivery date.
            // Clamp both ends to the visible window.
            $barStart  = 0;  // always starts at today's column
            $barEnd    = min(self::WINDOW_DAYS - 1, max(-1, $daysLeft));
            $barSpan   = max(0, $barEnd - $barStart + 1);

            // Urgency colour
            $colour = match (true) {
                $daysLeft <= 0  => 'danger',
                $daysLeft <= 2  => 'danger',
                $daysLeft <= 5  => 'warning',
                default         => 'success',
            };

            return [
                'orderId'        => $order->id,
                'ref'            => $order->whatsapp_order_id ?? $order->order_number,
                'orderNumber'    => $order->order_number,
                'customerName'   => $order->customer_name,
                'quantity'       => $order->quantity,
                'productType'    => $order->product_type_label,
                'priority'       => $order->priority,
                'status'         => $order->status,
                'deliveryDate'   => $deliveryDate,
                'daysLeft'       => $daysLeft,
                'barStart'       => $barStart,
                'barSpan'        => $barSpan,
                'colour'         => $colour,
                'isOverdue'      => $daysLeft < 0,
                'deliveryOffset' => min(self::WINDOW_DAYS - 1, max(0, $daysLeft)),
            ];
        })->values()->all();

        return view('schedule.gantt-designer', compact('dates', 'rows', 'today'));
    }
}
