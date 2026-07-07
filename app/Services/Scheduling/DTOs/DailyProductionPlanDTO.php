<?php

namespace App\Services\Scheduling\DTOs;

/**
 * Top-level result returned by SchedulingService.
 *
 * Holds all three department queues for a given day, plus cross-cutting
 * summaries consumed by the Pipeline Manager dashboard.
 */
final class DailyProductionPlanDTO
{
    public function __construct(
        public readonly string             $date,
        public readonly DepartmentQueueDTO $designQueue,
        public readonly DepartmentQueueDTO $printQueue,
        public readonly DepartmentQueueDTO $sewQueue,
    ) {}

    // ──────────────────────────────────────────────
    // Aggregated summaries
    // ──────────────────────────────────────────────

    /**
     * All departments that are in overtime today.
     *
     * @return DepartmentQueueDTO[]
     */
    public function overtimeDepartments(): array
    {
        return array_filter(
            [$this->designQueue, $this->printQueue, $this->sewQueue],
            fn (DepartmentQueueDTO $q) => $q->hasOvertime(),
        );
    }

    /**
     * Whether any department is in overtime today.
     */
    public function hasAnyOvertime(): bool
    {
        return count($this->overtimeDepartments()) > 0;
    }

    /**
     * All red-status orders across every department (deduplicated by order id).
     *
     * @return ScheduledOrderDTO[]
     */
    public function criticalOrders(): array
    {
        $seen   = [];
        $result = [];

        foreach ([$this->designQueue, $this->printQueue, $this->sewQueue] as $queue) {
            foreach ($queue->orders as $order) {
                if ($order->healthStatus === ScheduledOrderDTO::STATUS_RED
                    && ! isset($seen[$order->orderId])) {
                    $seen[$order->orderId] = true;
                    $result[]              = $order;
                }
            }
        }

        return $result;
    }

    /**
     * Late orders across all queues (deduplicated).
     *
     * @return ScheduledOrderDTO[]
     */
    public function lateOrders(): array
    {
        $seen   = [];
        $result = [];

        foreach ([$this->designQueue, $this->printQueue, $this->sewQueue] as $queue) {
            foreach ($queue->orders as $order) {
                if ($order->isLate && ! isset($seen[$order->orderId])) {
                    $seen[$order->orderId] = true;
                    $result[]              = $order;
                }
            }
        }

        return $result;
    }

    /**
     * Total units to be processed across all departments today.
     */
    public function totalJerseysToday(): int
    {
        return $this->designQueue->totalUnits()
            + $this->printQueue->totalUnits()
            + $this->sewQueue->totalUnits();
    }

    /**
     * Flat array of all overtime warning messages, one per overloaded dept.
     *
     * @return string[]
     */
    public function overtimeWarnings(): array
    {
        return array_values(
            array_filter(
                array_map(
                    fn (DepartmentQueueDTO $q) => $q->overtimeMessage(),
                    [$this->designQueue, $this->printQueue, $this->sewQueue],
                ),
            ),
        );
    }
}
