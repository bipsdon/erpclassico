<?php

namespace App\Services\Scheduling;

use App\Models\CapacityConfig;
use App\Models\Order;
use App\Models\ProductionSchedule;
use App\Services\Scheduling\DTOs\DailyProductionPlanDTO;
use App\Services\Scheduling\DTOs\DepartmentQueueDTO;
use App\Services\Scheduling\DTOs\ScheduledOrderDTO;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    /**
     * How many working days before delivery each department must finish.
     * Used for:
     *  (a) health-status colour
     *  (b) computing the latest acceptable start date for an order in that dept
     *
     * e.g. sew buffer = 1 → sewing must complete at least 1 day before delivery.
     * So the latest sewing can START is: delivery_date - buffer - ceil(qty/rate) + 1
     */
    public const BUFFER_DAYS = [
        'design' => 3,
        'print'  => 2,
        'sew'    => 1,
    ];

    private const PIPELINE = ['design', 'print', 'sew'];

    /** In-memory flag: date string → already rebuilt this request cycle. */
    private static array $rebuiltDates = [];

    // ──────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────

    public function buildDailyPlan(?Carbon $forDate = null): DailyProductionPlanDTO
    {
        $date = ($forDate ?? now())->startOfDay();

        $this->rebuildSchedules($date);

        return new DailyProductionPlanDTO(
            date:        $date->toDateString(),
            designQueue: $this->buildQueueForDepartment('design', $date),
            printQueue:  $this->buildQueueForDepartment('print',  $date),
            sewQueue:    $this->buildQueueForDepartment('sew',    $date),
        );
    }

    public function rebuildSchedules(?Carbon $asOf = null): void
    {
        $today   = ($asOf ?? now())->startOfDay();
        $dateKey = $today->toDateString();

        // Skip if already rebuilt during this request cycle AND we have a
        // Laravel cache entry for today. Invalidated by clearScheduleCache().
        $cacheKey = 'schedule_rebuilt_' . $dateKey;

        if (isset(self::$rebuiltDates[$dateKey]) || \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return;
        }

        $orders = Order::query()
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->whereNotIn('stage', ['delivered', 'ready'])
            ->with('productionSchedules')
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'rush' THEN 1 ELSE 2 END")
            ->orderBy('delivery_date')
            ->orderBy('id')
            ->get();

        // Running load fraction per dept per date.
        // ['sew' => ['2026-06-02' => 0.54, '2026-06-03' => 0.54, ...]]
        $loadByDeptDate = ['design' => [], 'print' => [], 'sew' => []];

        DB::transaction(function () use ($orders, $today, &$loadByDeptDate) {
            foreach ($orders as $order) {
                $this->scheduleOrder($order, $today, $loadByDeptDate);
            }
        });

        // Mark rebuilt for this request cycle and cache for 5 minutes.
        // Short TTL means the queue auto-refreshes without manual intervention,
        // while avoiding hammering the DB on every dashboard load within a burst.
        self::$rebuiltDates[$dateKey] = true;
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(5));
    }

    /**
     * Bust the schedule cache so the next dashboard load triggers a fresh rebuild.
     * Call this after any order create / update / stage-complete / delete.
     */
    public static function clearScheduleCache(?Carbon $forDate = null): void
    {
        $dateKey = ($forDate ?? now())->toDateString();
        self::$rebuiltDates = [];
        \Illuminate\Support\Facades\Cache::forget('schedule_rebuilt_' . $dateKey);
    }

    // ──────────────────────────────────────────────
    // Scheduling algorithm
    // ──────────────────────────────────────────────

    /**
     * Assign a scheduled_date to an order in its current department.
     *
     * Strategy — "fill the earliest available day, but don't exceed the deadline":
     *
     *   1. Compute latestStart — the last day work can BEGIN and still finish
     *      before the delivery buffer kicks in:
     *        latestStart = delivery_date - buffer_days - ceil(qty/rate) + 1
     *
     *   2. Walk forward from today:
     *        a. If today (or next available day) has spare capacity AND we are
     *           on or before latestStart → schedule here.
     *        b. If today is full AND we are still before latestStart → skip to
     *           tomorrow (no point forcing overtime when there's time to spare).
     *        c. If we reach latestStart and it's still full → schedule here
     *           anyway (overtime, flagged — missing it would mean a late delivery).
     *        d. If latestStart is already in the past → schedule today (red).
     *
     * Result:
     *   • Today has idle capacity → work is pulled in (queue stays busy).
     *   • Today is full but order has slack → pushed to tomorrow, no false OT.
     *   • Order is urgent / overdue → appears today regardless, shown red/OT.
     *
     * @param  array<string, array<string, float>>  $loadByDeptDate  Running load fractions, by ref.
     */
    private function scheduleOrder(Order $order, Carbon $today, array &$loadByDeptDate): void
    {
        $dept = $order->stage;

        if (! in_array($dept, self::PIPELINE)) {
            return;
        }

        // Skip if this department is not in the order's own pipeline.
        if (! in_array($dept, $order->effectivePipeline(), true)) {
            return;
        }

        $existing = $order->productionSchedules->firstWhere('department', $dept);

        if ($existing && $existing->completed_at !== null) {
            return;
        }

        // ── Design is uncapped — always schedule today, no capacity tracking ──
        if ($dept === 'design') {
            ProductionSchedule::updateOrCreate(
                ['order_id' => $order->id, 'department' => $dept],
                [
                    'scheduled_date'     => $today->toDateString(),
                    'quantity_scheduled' => $order->quantity,
                    'is_overtime'        => false,
                ],
            );

            return;
        }

        // ── Capped departments (print / sew) ──────────────────────────────────

        $rate     = CapacityConfig::rateFor($dept, $order->product_type);
        $fraction = $rate > 0 ? ($order->quantity / $rate) : 0.0;

        // Days this order occupies in this department (at least 1).
        $daysNeeded = $rate > 0 ? (int) ceil($order->quantity / $rate) : 1;

        // Latest day work can START and still honour the buffer before delivery.
        //   latestStart = delivery - buffer - daysNeeded + 1
        // e.g. delivery in 2 days, sew buffer 1, needs 1 day → latestStart = today
        //      meaning today is the last acceptable day.
        $buffer      = self::BUFFER_DAYS[$dept] ?? 1;
        $delivery    = Carbon::parse($order->delivery_date)->startOfDay();
        $latestStart = $delivery->copy()->subDays($buffer + $daysNeeded - 1);

        // ── Walk forward from today to find the best slot ─────────────────────
        $chosenDate = null;
        $candidate  = $today->copy();

        // Safety cap: never search beyond 90 days.
        $hardLimit = $today->copy()->addDays(90);

        while ($candidate->lte($hardLimit)) {
            $key         = $candidate->toDateString();
            $currentLoad = $loadByDeptDate[$dept][$key] ?? 0.0;

            $hasCapacity     = $currentLoad < 1.0;
            $atOrPastDeadline = $candidate->gte($latestStart);

            if ($hasCapacity || $atOrPastDeadline) {
                // Take this slot: it either has room, or we've run out of time
                // and must place it here regardless (overtime will be flagged).
                $chosenDate = $candidate->copy();
                break;
            }

            // Today is full AND we still have time — push to the next day.
            $candidate->addDay();
        }

        // Absolute fallback (should never be reached with a 90-day horizon).
        if ($chosenDate === null) {
            $chosenDate = $today->copy();
        }

        $dateKey = $chosenDate->toDateString();
        $loadByDeptDate[$dept][$dateKey] = ($loadByDeptDate[$dept][$dateKey] ?? 0.0) + $fraction;
        $isOvertime = $loadByDeptDate[$dept][$dateKey] > 1.0;

        ProductionSchedule::updateOrCreate(
            ['order_id' => $order->id, 'department' => $dept],
            [
                'scheduled_date'     => $dateKey,
                'quantity_scheduled' => $order->quantity,
                'is_overtime'        => $isOvertime,
            ],
        );
    }

    // ──────────────────────────────────────────────
    // Queue builder
    // ──────────────────────────────────────────────

    /**
     * Build the queue for a department on a given date.
     *
     * Only orders whose scheduled_date matches the requested date exactly are
     * included. Using <= caused every future order to flood today's queue as
     * days passed.
     */
    private function buildQueueForDepartment(
        string $department,
        Carbon $date,
    ): DepartmentQueueDTO {
        $dateString = $date->toDateString();

        $slots = ProductionSchedule::query()
            ->where('department', $department)
            ->whereDate('scheduled_date', $dateString)   // exact date — not <=
            ->whereNull('completed_at')
            ->with('order')
            ->get();

        $dtoList            = [];
        $totalLoad          = 0.0;
        $unitsByProductType = [];

        foreach ($slots as $slot) {
            if ($slot->order === null || $slot->order->status === 'cancelled') {
                continue;
            }

            $dto = ScheduledOrderDTO::fromOrder(
                order:         $slot->order,
                department:    $department,
                scheduledDate: $dateString,
                isOvertime:    $slot->is_overtime,
            );

            $dtoList[] = $dto;

            $totalLoad += $dto->dayFraction;

            $pt = $dto->productType;
            $unitsByProductType[$pt] = ($unitsByProductType[$pt] ?? 0) + $dto->quantity;
        }

        usort($dtoList, static function (ScheduledOrderDTO $a, ScheduledOrderDTO $b): int {
            $rank = ['critical' => 0, 'rush' => 1, 'normal' => 2];
            $diff = ($rank[$a->priority] ?? 9) - ($rank[$b->priority] ?? 9);

            return $diff !== 0 ? $diff : strcmp($a->deliveryDate, $b->deliveryDate);
        });

        return new DepartmentQueueDTO(
            department:         $department,
            date:               $dateString,
            totalLoad:          $department === 'design' ? 0.0 : $totalLoad,
            orders:             $dtoList,
            unitsByProductType: $unitsByProductType,
        );
    }

    // ──────────────────────────────────────────────
    // Tomorrow queue (for "Coming Tomorrow" cards)
    // ──────────────────────────────────────────────

    /**
     * Return the queue for a department for tomorrow's date.
     * Used by department dashboards to show the next-day workload.
     */
    public function buildTomorrowQueue(string $department, ?Carbon $asOf = null): DepartmentQueueDTO
    {
        $tomorrow = ($asOf ?? now())->startOfDay()->addDay();

        return $this->buildQueueForDepartment($department, $tomorrow);
    }
}
