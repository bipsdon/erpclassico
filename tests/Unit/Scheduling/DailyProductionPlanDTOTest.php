<?php

namespace Tests\Unit\Scheduling;

use App\Services\Scheduling\DTOs\DailyProductionPlanDTO;
use App\Services\Scheduling\DTOs\DepartmentQueueDTO;
use App\Services\Scheduling\DTOs\ScheduledOrderDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DailyProductionPlanDTOTest extends TestCase
{
    // ──────────────────────────────────────────────
    // Overtime aggregation
    // ──────────────────────────────────────────────

    #[Test]
    public function has_any_overtime_is_false_when_all_within_capacity(): void
    {
        $plan = $this->makePlan(printLoad: 0.67, sewLoad: 0.625); // both < 1.0

        $this->assertFalse($plan->hasAnyOvertime());
        $this->assertCount(0, $plan->overtimeDepartments());
    }

    #[Test]
    public function detects_printing_overtime(): void
    {
        $plan = $this->makePlan(printLoad: 1.4, sewLoad: 0.5); // print over, sew OK

        $this->assertTrue($plan->hasAnyOvertime());

        $depts = array_map(fn ($q) => $q->department, $plan->overtimeDepartments());
        $this->assertContains('print', $depts);
        $this->assertNotContains('sew', $depts);
    }

    #[Test]
    public function detects_sewing_overtime(): void
    {
        $plan = $this->makePlan(printLoad: 0.5, sewLoad: 1.5);

        $this->assertTrue($plan->hasAnyOvertime());

        $depts = array_map(fn ($q) => $q->department, $plan->overtimeDepartments());
        $this->assertContains('sew', $depts);
        $this->assertNotContains('print', $depts);
    }

    #[Test]
    public function detects_both_departments_in_overtime(): void
    {
        $plan = $this->makePlan(printLoad: 1.4, sewLoad: 1.5);
        $this->assertCount(2, $plan->overtimeDepartments());
    }

    // ──────────────────────────────────────────────
    // Overtime warnings
    // ──────────────────────────────────────────────

    #[Test]
    public function overtime_warnings_count_matches_overtime_departments(): void
    {
        $plan = $this->makePlan(printLoad: 1.4, sewLoad: 1.5);
        $this->assertCount(2, $plan->overtimeWarnings());
    }

    #[Test]
    public function overtime_warnings_is_empty_when_within_capacity(): void
    {
        $plan = $this->makePlan(printLoad: 0.5, sewLoad: 0.5);
        $this->assertEmpty($plan->overtimeWarnings());
    }

    // ──────────────────────────────────────────────
    // Critical orders
    // ──────────────────────────────────────────────

    #[Test]
    public function critical_orders_are_deduplicated_across_departments(): void
    {
        $o1 = $this->makeOrderDTO(id: 1, health: ScheduledOrderDTO::STATUS_RED);
        $o2 = $this->makeOrderDTO(id: 1, health: ScheduledOrderDTO::STATUS_RED); // duplicate
        $o3 = $this->makeOrderDTO(id: 2, health: ScheduledOrderDTO::STATUS_RED);

        $plan = $this->makePlanWithOrders(designOrders: [$o1], printOrders: [$o2, $o3]);

        $this->assertCount(2, $plan->criticalOrders());
    }

    #[Test]
    public function non_red_orders_not_in_critical_list(): void
    {
        $plan = $this->makePlanWithOrders(designOrders: [
            $this->makeOrderDTO(id: 10, health: ScheduledOrderDTO::STATUS_GREEN),
            $this->makeOrderDTO(id: 11, health: ScheduledOrderDTO::STATUS_YELLOW),
        ]);

        $this->assertEmpty($plan->criticalOrders());
    }

    // ──────────────────────────────────────────────
    // Late orders
    // ──────────────────────────────────────────────

    #[Test]
    public function late_orders_are_collected_and_deduplicated(): void
    {
        $l1 = $this->makeOrderDTO(id: 5, health: ScheduledOrderDTO::STATUS_RED, isLate: true);
        $l2 = $this->makeOrderDTO(id: 5, health: ScheduledOrderDTO::STATUS_RED, isLate: true); // dup
        $l3 = $this->makeOrderDTO(id: 6, health: ScheduledOrderDTO::STATUS_RED, isLate: true);

        $plan = $this->makePlanWithOrders(
            designOrders: [$l1],
            printOrders:  [$l2],
            sewOrders:    [$l3],
        );

        $this->assertCount(2, $plan->lateOrders());
    }

    // ──────────────────────────────────────────────
    // Total units today
    // ──────────────────────────────────────────────

    #[Test]
    public function total_units_today_sums_all_departments(): void
    {
        $plan = $this->makePlanWithOrders(
            designOrders: [$this->makeOrderDTO(id: 1, quantity: 50)],
            printOrders:  [$this->makeOrderDTO(id: 2, quantity: 200)],
            sewOrders:    [$this->makeOrderDTO(id: 3, quantity: 80)],
        );

        $this->assertSame(330, $plan->totalJerseysToday());
    }

    #[Test]
    public function total_units_is_zero_for_empty_plan(): void
    {
        $this->assertSame(0, $this->makePlan()->totalJerseysToday());
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function makePlan(
        float $designLoad = 0.0,
        float $printLoad  = 0.0,
        float $sewLoad    = 0.0,
    ): DailyProductionPlanDTO {
        return $this->makePlanWithOrders(
            designLoad: $designLoad,
            printLoad:  $printLoad,
            sewLoad:    $sewLoad,
        );
    }

    private function makePlanWithOrders(
        array $designOrders = [],
        array $printOrders  = [],
        array $sewOrders    = [],
        float $designLoad   = 0.0,
        float $printLoad    = 0.0,
        float $sewLoad      = 0.0,
    ): DailyProductionPlanDTO {
        $date = now()->toDateString();

        // Derive load and units from orders if supplied
        $designUnits = [];
        $printUnits  = [];
        $sewUnits    = [];

        foreach ($designOrders as $o) {
            $designUnits['jersey'] = ($designUnits['jersey'] ?? 0) + $o->quantity;
        }
        foreach ($printOrders as $o) {
            $printUnits['jersey'] = ($printUnits['jersey'] ?? 0) + $o->quantity;
            $printLoad += $o->dayFraction;
        }
        foreach ($sewOrders as $o) {
            $sewUnits['jersey'] = ($sewUnits['jersey'] ?? 0) + $o->quantity;
            $sewLoad += $o->dayFraction;
        }

        return new DailyProductionPlanDTO(
            date:        $date,
            designQueue: new DepartmentQueueDTO('design', $date, 0.0,       $designOrders, $designUnits),
            printQueue:  new DepartmentQueueDTO('print',  $date, $printLoad, $printOrders,  $printUnits),
            sewQueue:    new DepartmentQueueDTO('sew',    $date, $sewLoad,   $sewOrders,    $sewUnits),
        );
    }

    private function makeOrderDTO(
        int    $id,
        string $health   = ScheduledOrderDTO::STATUS_GREEN,
        bool   $isLate   = false,
        int    $quantity = 10,
    ): ScheduledOrderDTO {
        return new ScheduledOrderDTO(
            orderId:           $id,
            orderNumber:       "ORD-TEST-{$id}",
            customerName:      "Customer {$id}",
            quantity:          $quantity,
            productType:       'jersey',
            productTypeLabel:  'Jersey',
            deliveryDate:      now()->addDays(3)->toDateString(),
            priority:          'normal',
            department:        'design',
            scheduledDate:     now()->toDateString(),
            isOvertime:        false,
            daysUntilDelivery: $isLate ? -2 : 3,
            healthStatus:      $health,
            isLate:            $isLate,
            dayFraction:       $quantity / 80.0, // assume jersey at 80/day
        );
    }
}
