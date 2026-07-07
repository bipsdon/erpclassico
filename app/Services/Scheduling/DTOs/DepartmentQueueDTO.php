<?php

namespace App\Services\Scheduling\DTOs;

/**
 * Aggregated daily queue for a single department.
 *
 * Workload is expressed as a fraction of one working day, computed by summing
 * (quantity / units_per_day) across all orders. This correctly handles mixed
 * product types — a tracksuit takes more time per unit than a jersey, so it
 * contributes a larger fraction of the day regardless of raw unit count.
 *
 * Examples:
 *   40 jerseys (rate 80/day)    = 0.50 days  (50%)
 *   30 tracksuits (rate 55/day) = 0.545 days (54.5%)
 *   Total = 1.045 days          → 4.5% overtime
 */
final class DepartmentQueueDTO
{
    /** @param ScheduledOrderDTO[] $orders */
    public function __construct(
        public readonly string $department,
        public readonly string $date,
        /**
         * Total workload as a fraction of one working day.
         * 0.0 = no work, 1.0 = exactly full day, 1.5 = 50% overtime.
         * 0 for design (uncapped).
         */
        public readonly float  $totalLoad,
        public readonly array  $orders,
        /**
         * Raw unit counts per product type for display.
         * ['jersey' => 40, 'tracksuit' => 30, ...]
         */
        public readonly array  $unitsByProductType,
    ) {}

    // ──────────────────────────────────────────────
    // Workload metrics
    // ──────────────────────────────────────────────

    /**
     * Total workload as an integer percentage of one working day.
     * 150 means 1.5 days of work = 50% overtime.
     * Returns null for uncapped departments (design).
     */
    public function loadPercent(): ?int
    {
        if ($this->totalLoad === 0.0 && $this->department === 'design') {
            return null;
        }

        return (int) round($this->totalLoad * 100);
    }

    /**
     * Whether today requires overtime (load > 100% of a day).
     */
    public function hasOvertime(): bool
    {
        return $this->totalLoad > 1.0;
    }

    /**
     * Overtime as a percentage of a working day (0 if no overtime).
     * e.g. if load = 1.45, overtime = 45%
     */
    public function overtimePercent(): int
    {
        return (int) max(0, round(($this->totalLoad - 1.0) * 100));
    }

    /**
     * Total units across all product types.
     */
    public function totalUnits(): int
    {
        return array_sum($this->unitsByProductType);
    }

    // ──────────────────────────────────────────────
    // Dashboard colour logic
    // ──────────────────────────────────────────────

    /**
     * Bootstrap colour class for the utilisation bar.
     *
     * Framed positively — high utilisation is good:
     *   < 50%    → info    (underutilised, blue)
     *   50-99%   → success (healthy, green)
     *   100-149% → warning (overtime, yellow)
     *   ≥ 150%   → danger  (heavy overtime, red)
     */
    public function utilisationColour(): string
    {
        $pct = $this->loadPercent();

        if ($pct === null) {
            return 'info';
        }

        return match (true) {
            $pct >= 150 => 'danger',
            $pct >= 100 => 'warning',
            $pct >= 50  => 'success',
            default     => 'info',
        };
    }

    /**
     * Width of the "normal capacity" bar segment (capped at 100%).
     */
    public function normalBarWidth(): int
    {
        return (int) min(100, round($this->totalLoad * 100));
    }

    /**
     * Width of the overtime bar segment (the portion beyond 100%).
     * Scaled down so the bar doesn't overflow the container.
     */
    public function overtimeBarWidth(): int
    {
        if (! $this->hasOvertime()) {
            return 0;
        }

        // Cap visual representation at +50% over capacity for display
        return (int) min(50, round(($this->totalLoad - 1.0) * 100));
    }

    // ──────────────────────────────────────────────
    // Summaries
    // ──────────────────────────────────────────────

    /** @return array{green: int, yellow: int, red: int} */
    public function healthSummary(): array
    {
        $summary = ['green' => 0, 'yellow' => 0, 'red' => 0];

        foreach ($this->orders as $order) {
            $summary[$order->healthStatus]++;
        }

        return $summary;
    }

    public function departmentLabel(): string
    {
        return match ($this->department) {
            'design' => 'Design',
            'print'  => 'Printing',
            'sew'    => 'Sewing',
            default  => ucfirst($this->department),
        };
    }

    public function overtimeMessage(): ?string
    {
        if (! $this->hasOvertime()) {
            return null;
        }

        return sprintf(
            '%s OVERTIME REQUIRED — %d%% of an extra day needed (Total workload: %d%%)',
            strtoupper($this->departmentLabel()),
            $this->overtimePercent(),
            $this->loadPercent(),
        );
    }
}
