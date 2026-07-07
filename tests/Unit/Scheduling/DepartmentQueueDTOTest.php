<?php

namespace Tests\Unit\Scheduling;

use App\Services\Scheduling\DTOs\DepartmentQueueDTO;
use App\Services\Scheduling\DTOs\ScheduledOrderDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DepartmentQueueDTO — load-fraction-based capacity model.
 *
 * Capacity is expressed as a fraction of a working day.
 * totalLoad = sum(quantity / units_per_day) per order.
 * 1.0 = exactly one full day. >1.0 = overtime.
 */
class DepartmentQueueDTOTest extends TestCase
{
    // ──────────────────────────────────────────────
    // Overtime detection
    // ──────────────────────────────────────────────

    #[Test]
    public function no_overtime_when_load_below_one(): void
    {
        // 60 jerseys at 80/day = 0.75 → no overtime
        $queue = $this->makeQueue(totalLoad: 0.75);
        $this->assertFalse($queue->hasOvertime());
        $this->assertSame(0, $queue->overtimePercent());
    }

    #[Test]
    public function no_overtime_at_exact_full_day(): void
    {
        // Exactly 1.0 = 100% of a day — no overtime
        $queue = $this->makeQueue(totalLoad: 1.0);
        $this->assertFalse($queue->hasOvertime());
        $this->assertSame(0, $queue->overtimePercent());
    }

    #[Test]
    public function overtime_when_load_exceeds_one(): void
    {
        // 1.5 = 150% workload — 50% overtime
        $queue = $this->makeQueue(totalLoad: 1.5);
        $this->assertTrue($queue->hasOvertime());
        $this->assertSame(50, $queue->overtimePercent());
    }

    #[Test]
    public function sewing_mixed_product_example(): void
    {
        // 40 jerseys (80/day) = 0.50  +  30 tracksuits (55/day) = 0.545 → total 1.045
        $load  = (40 / 80) + (30 / 55);
        $queue = $this->makeQueue(totalLoad: $load);

        $this->assertTrue($queue->hasOvertime());
        // overtime = 4.5% of a day
        $this->assertSame(5, $queue->overtimePercent()); // rounds to 5
    }

    #[Test]
    public function no_overtime_for_design_uncapped(): void
    {
        // design passes 0.0 load
        $queue = $this->makeQueue(totalLoad: 0.0, department: 'design');
        $this->assertFalse($queue->hasOvertime());
        $this->assertNull($queue->loadPercent());
    }

    // ──────────────────────────────────────────────
    // Load percent
    // ──────────────────────────────────────────────

    #[Test]
    public function load_percent_at_half_day(): void
    {
        $this->assertSame(50, $this->makeQueue(totalLoad: 0.5)->loadPercent());
    }

    #[Test]
    public function load_percent_at_full_day(): void
    {
        $this->assertSame(100, $this->makeQueue(totalLoad: 1.0)->loadPercent());
    }

    #[Test]
    public function load_percent_can_exceed_100_for_overtime(): void
    {
        // Unlike the old model, load percent is NOT capped at 100
        $this->assertSame(140, $this->makeQueue(totalLoad: 1.4)->loadPercent());
    }

    // ──────────────────────────────────────────────
    // Utilisation colour — positive framing
    // ──────────────────────────────────────────────

    #[Test]
    public function utilisation_colour_is_info_when_underutilised(): void
    {
        // < 50% → blue (underutilised, capacity being wasted)
        $this->assertSame('info', $this->makeQueue(totalLoad: 0.3)->utilisationColour());
    }

    #[Test]
    public function utilisation_colour_is_success_when_healthy(): void
    {
        // 50–99% → green (good utilisation)
        $this->assertSame('success', $this->makeQueue(totalLoad: 0.75)->utilisationColour());
    }

    #[Test]
    public function utilisation_colour_is_warning_when_overtime(): void
    {
        // 100–149% → yellow (overtime but manageable)
        $this->assertSame('warning', $this->makeQueue(totalLoad: 1.2)->utilisationColour());
    }

    #[Test]
    public function utilisation_colour_is_danger_when_heavy_overtime(): void
    {
        // >= 150% → red (heavy overtime)
        $this->assertSame('danger', $this->makeQueue(totalLoad: 1.6)->utilisationColour());
    }

    #[Test]
    public function utilisation_colour_is_info_for_uncapped_design(): void
    {
        $this->assertSame('info', $this->makeQueue(totalLoad: 0.0, department: 'design')->utilisationColour());
    }

    // ──────────────────────────────────────────────
    // Bar widths
    // ──────────────────────────────────────────────

    #[Test]
    public function normal_bar_width_capped_at_100(): void
    {
        // Even if load is 1.5, the base bar only shows 100%
        $queue = $this->makeQueue(totalLoad: 1.5);
        $this->assertSame(100, $queue->normalBarWidth());
    }

    #[Test]
    public function normal_bar_width_reflects_load_when_under(): void
    {
        $queue = $this->makeQueue(totalLoad: 0.75);
        $this->assertSame(75, $queue->normalBarWidth());
    }

    #[Test]
    public function overtime_bar_width_zero_when_no_overtime(): void
    {
        $this->assertSame(0, $this->makeQueue(totalLoad: 0.9)->overtimeBarWidth());
    }

    #[Test]
    public function overtime_bar_width_reflects_excess(): void
    {
        // 1.3 → 30% overtime
        $queue = $this->makeQueue(totalLoad: 1.3);
        $this->assertSame(30, $queue->overtimeBarWidth());
    }

    #[Test]
    public function overtime_bar_width_capped_at_50_for_display(): void
    {
        // 2.0 → 100% overtime, but capped at 50 for display
        $queue = $this->makeQueue(totalLoad: 2.0);
        $this->assertSame(50, $queue->overtimeBarWidth());
    }

    // ──────────────────────────────────────────────
    // Total units
    // ──────────────────────────────────────────────

    #[Test]
    public function total_units_sums_units_by_product_type(): void
    {
        $queue = new DepartmentQueueDTO(
            department:         'sew',
            date:               now()->toDateString(),
            totalLoad:          1.0,
            orders:             [],
            unitsByProductType: ['jersey' => 40, 'tracksuit' => 30],
        );

        $this->assertSame(70, $queue->totalUnits());
    }

    #[Test]
    public function empty_queue_has_zero_total_units(): void
    {
        $queue = $this->makeQueue(totalLoad: 0.0);
        $this->assertSame(0, $queue->totalUnits());
    }

    // ──────────────────────────────────────────────
    // Health summary
    // ──────────────────────────────────────────────

    #[Test]
    public function health_summary_counts_correctly(): void
    {
        $orders = [
            $this->makeOrderDTO('green'),
            $this->makeOrderDTO('green'),
            $this->makeOrderDTO('yellow'),
            $this->makeOrderDTO('red'),
            $this->makeOrderDTO('red'),
            $this->makeOrderDTO('red'),
        ];

        $queue = new DepartmentQueueDTO(
            department:         'sew',
            date:               now()->toDateString(),
            totalLoad:          0.75,
            orders:             $orders,
            unitsByProductType: ['jersey' => 60],
        );

        $summary = $queue->healthSummary();
        $this->assertSame(2, $summary['green']);
        $this->assertSame(1, $summary['yellow']);
        $this->assertSame(3, $summary['red']);
    }

    #[Test]
    public function empty_queue_has_zero_health_counts(): void
    {
        $summary = $this->makeQueue(totalLoad: 0.0)->healthSummary();
        $this->assertSame(0, $summary['green']);
        $this->assertSame(0, $summary['yellow']);
        $this->assertSame(0, $summary['red']);
    }

    // ──────────────────────────────────────────────
    // Overtime message
    // ──────────────────────────────────────────────

    #[Test]
    public function overtime_message_is_null_when_no_overtime(): void
    {
        $this->assertNull($this->makeQueue(totalLoad: 0.75, department: 'print')->overtimeMessage());
    }

    #[Test]
    public function overtime_message_contains_department_and_percentages(): void
    {
        // 1.4 = 140% workload, 40% overtime
        $queue   = $this->makeQueue(totalLoad: 1.4, department: 'print');
        $message = $queue->overtimeMessage();

        $this->assertNotNull($message);
        $this->assertStringContainsString('PRINTING', $message);
        $this->assertStringContainsString('OVERTIME', $message);
        $this->assertStringContainsString('40', $message);  // overtime %
        $this->assertStringContainsString('140', $message); // total load %
    }

    #[Test]
    public function sewing_overtime_message_contains_sewing(): void
    {
        $message = $this->makeQueue(totalLoad: 1.5, department: 'sew')->overtimeMessage();
        $this->assertStringContainsString('SEWING', $message);
        $this->assertStringContainsString('OVERTIME', $message);
    }

    // ──────────────────────────────────────────────
    // Department labels
    // ──────────────────────────────────────────────

    #[Test]
    public function department_labels_are_human_readable(): void
    {
        $this->assertSame('Design',   $this->makeQueue(0.0, 'design')->departmentLabel());
        $this->assertSame('Printing', $this->makeQueue(0.0, 'print')->departmentLabel());
        $this->assertSame('Sewing',   $this->makeQueue(0.0, 'sew')->departmentLabel());
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function makeQueue(float $totalLoad, string $department = 'print'): DepartmentQueueDTO
    {
        return new DepartmentQueueDTO(
            department:         $department,
            date:               now()->toDateString(),
            totalLoad:          $department === 'design' ? 0.0 : $totalLoad,
            orders:             [],
            unitsByProductType: [],
        );
    }

    private function makeOrderDTO(string $healthStatus): ScheduledOrderDTO
    {
        static $id = 0;
        $id++;

        return new ScheduledOrderDTO(
            orderId:           $id,
            orderNumber:       "ORD-TEST-{$id}",
            customerName:      "Customer {$id}",
            quantity:          10,
            productType:       'jersey',
            productTypeLabel:  'Jersey',
            deliveryDate:      now()->addDays(3)->toDateString(),
            priority:          'normal',
            department:        'sew',
            scheduledDate:     now()->toDateString(),
            isOvertime:        false,
            daysUntilDelivery: 3,
            healthStatus:      $healthStatus,
            isLate:            false,
            dayFraction:       0.125,
        );
    }
}
