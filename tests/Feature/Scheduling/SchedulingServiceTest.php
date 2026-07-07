<?php

namespace Tests\Feature\Scheduling;

use App\Models\CapacityConfig;
use App\Models\Order;
use App\Models\ProductionSchedule;
use App\Models\User;
use App\Services\Scheduling\DTOs\ScheduledOrderDTO;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SchedulingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SchedulingService $service;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SchedulingService::class);

        $this->manager = User::factory()->create(['role' => 'pipeline_manager']);

        // Seed per-product-type capacity rates
        $configs = [
            ['department' => 'print', 'product_type' => 'jersey',     'units_per_day' => 300],
            ['department' => 'print', 'product_type' => 'tracksuit',  'units_per_day' => 200],
            ['department' => 'print', 'product_type' => 'polo_shirt', 'units_per_day' => 250],
            ['department' => 'print', 'product_type' => 'shorts',     'units_per_day' => 350],
            ['department' => 'print', 'product_type' => 'other',      'units_per_day' => 200],
            ['department' => 'sew',   'product_type' => 'jersey',     'units_per_day' => 80],
            ['department' => 'sew',   'product_type' => 'tracksuit',  'units_per_day' => 55],
            ['department' => 'sew',   'product_type' => 'polo_shirt', 'units_per_day' => 75],
            ['department' => 'sew',   'product_type' => 'shorts',     'units_per_day' => 100],
            ['department' => 'sew',   'product_type' => 'other',      'units_per_day' => 60],
        ];

        foreach ($configs as $c) {
            CapacityConfig::create([
                'department'    => $c['department'],
                'product_type'  => $c['product_type'],
                'units_per_day' => $c['units_per_day'],
                'updated_by'    => $this->manager->id,
                'updated_at'    => now(),
            ]);
        }
    }

    // ──────────────────────────────────────────────
    // Queue membership
    // ──────────────────────────────────────────────

    #[Test]
    public function order_in_design_stage_appears_in_design_queue(): void
    {
        $this->createOrder(stage: 'design', quantity: 20, daysUntilDelivery: 5);
        $plan = $this->service->buildDailyPlan();

        $this->assertCount(1, $plan->designQueue->orders);
        $this->assertCount(0, $plan->printQueue->orders);
        $this->assertCount(0, $plan->sewQueue->orders);
    }

    #[Test]
    public function order_in_print_stage_appears_in_print_queue(): void
    {
        $this->createOrder(stage: 'print', quantity: 30, daysUntilDelivery: 3);
        $plan = $this->service->buildDailyPlan();

        $this->assertCount(0, $plan->designQueue->orders);
        $this->assertCount(1, $plan->printQueue->orders);
        $this->assertCount(0, $plan->sewQueue->orders);
    }

    #[Test]
    public function order_in_sew_stage_appears_in_sew_queue(): void
    {
        $this->createOrder(stage: 'sew', quantity: 40, daysUntilDelivery: 2);
        $plan = $this->service->buildDailyPlan();

        $this->assertCount(0, $plan->designQueue->orders);
        $this->assertCount(0, $plan->printQueue->orders);
        $this->assertCount(1, $plan->sewQueue->orders);
    }

    #[Test]
    public function delivered_orders_do_not_appear_in_any_queue(): void
    {
        $this->createOrder(stage: 'delivered', quantity: 22, daysUntilDelivery: -1);
        $plan = $this->service->buildDailyPlan();

        $this->assertCount(0, $plan->designQueue->orders);
        $this->assertCount(0, $plan->printQueue->orders);
        $this->assertCount(0, $plan->sewQueue->orders);
    }

    #[Test]
    public function cancelled_orders_do_not_appear_in_any_queue(): void
    {
        $this->createOrder(stage: 'design', quantity: 15, status: 'cancelled', daysUntilDelivery: 5);
        $plan = $this->service->buildDailyPlan();

        $this->assertCount(0, $plan->designQueue->orders);
    }

    #[Test]
    public function ready_for_delivery_orders_are_excluded(): void
    {
        $this->createOrder(stage: 'ready', quantity: 22, daysUntilDelivery: 1);
        $plan = $this->service->buildDailyPlan();

        $this->assertCount(0, $plan->designQueue->orders);
        $this->assertCount(0, $plan->printQueue->orders);
        $this->assertCount(0, $plan->sewQueue->orders);
    }

    // ──────────────────────────────────────────────
    // Capacity and overtime (load-fraction model)
    // ──────────────────────────────────────────────

    #[Test]
    public function no_overtime_when_sewing_jerseys_at_exactly_80(): void
    {
        // 80 jerseys at 80/day = 1.0 exactly — no overtime
        $this->createOrder(stage: 'sew', quantity: 80, productType: 'jersey', daysUntilDelivery: 2);
        $plan = $this->service->buildDailyPlan();

        $this->assertFalse($plan->sewQueue->hasOvertime());
        $this->assertSame(0, $plan->sewQueue->overtimePercent());
    }

    #[Test]
    public function overtime_triggered_when_jersey_sewing_exceeds_80(): void
    {
        // 50 + 50 = 100 jerseys at 80/day = 1.25 → overtime
        $this->createOrder(stage: 'sew', quantity: 50, productType: 'jersey', daysUntilDelivery: 1);
        $this->createOrder(stage: 'sew', quantity: 50, productType: 'jersey', daysUntilDelivery: 2);
        $plan = $this->service->buildDailyPlan();

        $this->assertTrue($plan->sewQueue->hasOvertime());
        $this->assertSame(25, $plan->sewQueue->overtimePercent()); // 25% overtime
    }

    #[Test]
    public function mixed_product_types_combine_correctly_in_sewing(): void
    {
        // 40 jerseys (80/day) = 0.50 + 30 tracksuits (55/day) ≈ 0.545 → total ≈ 1.045 → overtime
        $this->createOrder(stage: 'sew', quantity: 40, productType: 'jersey',    daysUntilDelivery: 2);
        $this->createOrder(stage: 'sew', quantity: 30, productType: 'tracksuit', daysUntilDelivery: 3);
        $plan = $this->service->buildDailyPlan();

        $this->assertTrue($plan->sewQueue->hasOvertime());
        // load ≈ 1.045, overtime ≈ 4-5%
        $this->assertGreaterThan(0, $plan->sewQueue->overtimePercent());
        $this->assertLessThan(10, $plan->sewQueue->overtimePercent());
    }

    #[Test]
    public function tracksuits_consume_more_sewing_capacity_than_jerseys(): void
    {
        // 55 tracksuits at 55/day = exactly 1.0 → no overtime
        $this->createOrder(stage: 'sew', quantity: 55, productType: 'tracksuit', daysUntilDelivery: 3);
        $plan = $this->service->buildDailyPlan();

        $this->assertFalse($plan->sewQueue->hasOvertime());
        $this->assertSame(100, $plan->sewQueue->loadPercent());
    }

    #[Test]
    public function overtime_triggered_when_printing_jerseys_exceed_300(): void
    {
        // 4 × 80 = 320 jerseys at 300/day = 1.067 → overtime
        for ($i = 0; $i < 4; $i++) {
            $this->createOrder(stage: 'print', quantity: 80, productType: 'jersey', daysUntilDelivery: 3);
        }
        $plan = $this->service->buildDailyPlan();

        $this->assertTrue($plan->printQueue->hasOvertime());
        $this->assertGreaterThan(0, $plan->printQueue->overtimePercent());
    }

    #[Test]
    public function overtime_message_contains_overtime_and_department(): void
    {
        // 210 + 210 = 420 jerseys at 300/day = 1.4 → 40% overtime
        $this->createOrder(stage: 'print', quantity: 210, productType: 'jersey', daysUntilDelivery: 3);
        $this->createOrder(stage: 'print', quantity: 210, productType: 'jersey', daysUntilDelivery: 4);

        $message = $this->service->buildDailyPlan()->printQueue->overtimeMessage();

        $this->assertNotNull($message);
        $this->assertStringContainsString('PRINTING', $message);
        $this->assertStringContainsString('OVERTIME', $message);
        $this->assertStringContainsString('40', $message); // 40% overtime
    }

    #[Test]
    public function design_queue_never_triggers_overtime(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->createOrder(stage: 'design', quantity: 500, daysUntilDelivery: 5);
        }
        $plan = $this->service->buildDailyPlan();

        $this->assertFalse($plan->designQueue->hasOvertime());
        $this->assertNull($plan->designQueue->loadPercent());
    }

    // ──────────────────────────────────────────────
    // Priority ordering
    // ──────────────────────────────────────────────

    #[Test]
    public function critical_orders_appear_before_rush_and_normal(): void
    {
        $this->createOrder(stage: 'print', quantity: 10, priority: 'normal',   daysUntilDelivery: 5);
        $this->createOrder(stage: 'print', quantity: 10, priority: 'rush',     daysUntilDelivery: 4);
        $this->createOrder(stage: 'print', quantity: 10, priority: 'critical', daysUntilDelivery: 6);

        $orders = $this->service->buildDailyPlan()->printQueue->orders;

        $this->assertSame('critical', $orders[0]->priority);
        $this->assertSame('rush',     $orders[1]->priority);
        $this->assertSame('normal',   $orders[2]->priority);
    }

    #[Test]
    public function within_same_priority_earlier_delivery_comes_first(): void
    {
        $today = now();
        $this->createOrder(stage: 'sew', quantity: 10, priority: 'normal', daysUntilDelivery: 5);
        $this->createOrder(stage: 'sew', quantity: 10, priority: 'normal', daysUntilDelivery: 2);
        $this->createOrder(stage: 'sew', quantity: 10, priority: 'normal', daysUntilDelivery: 8);

        $orders = $this->service->buildDailyPlan()->sewQueue->orders;

        $this->assertSame($today->copy()->addDays(2)->toDateString(), $orders[0]->deliveryDate);
        $this->assertSame($today->copy()->addDays(5)->toDateString(), $orders[1]->deliveryDate);
        $this->assertSame($today->copy()->addDays(8)->toDateString(), $orders[2]->deliveryDate);
    }

    // ──────────────────────────────────────────────
    // Health status
    // ──────────────────────────────────────────────

    #[Test]
    public function normal_order_with_ample_time_is_green(): void
    {
        $this->createOrder(stage: 'design', quantity: 10, priority: 'normal', daysUntilDelivery: 7);
        $dto = $this->service->buildDailyPlan()->designQueue->orders[0];

        $this->assertSame(ScheduledOrderDTO::STATUS_GREEN, $dto->healthStatus);
    }

    #[Test]
    public function critical_order_is_always_red_regardless_of_days(): void
    {
        $this->createOrder(stage: 'design', quantity: 10, priority: 'critical', daysUntilDelivery: 10);
        $dto = $this->service->buildDailyPlan()->designQueue->orders[0];

        $this->assertSame(ScheduledOrderDTO::STATUS_RED, $dto->healthStatus);
    }

    #[Test]
    public function overdue_order_is_late_and_red(): void
    {
        $this->createOrder(stage: 'design', quantity: 10, priority: 'normal', daysUntilDelivery: -3);
        $dto = $this->service->buildDailyPlan()->designQueue->orders[0];

        $this->assertTrue($dto->isLate);
        $this->assertSame(ScheduledOrderDTO::STATUS_RED, $dto->healthStatus);
    }

    // ──────────────────────────────────────────────
    // Schedule rebuilding
    // ──────────────────────────────────────────────

    #[Test]
    public function completed_schedule_slot_is_excluded_from_queue(): void
    {
        $order = $this->createOrder(stage: 'print', quantity: 20, daysUntilDelivery: 3);

        ProductionSchedule::updateOrCreate(
            ['order_id' => $order->id, 'department' => 'print'],
            [
                'scheduled_date'     => now()->toDateString(),
                'quantity_scheduled' => 20,
                'is_overtime'        => false,
                'completed_at'       => now(),
                'completed_by'       => $this->manager->id,
            ],
        );

        $plan = $this->service->buildDailyPlan();
        $this->assertCount(0, $plan->printQueue->orders);
    }

    #[Test]
    public function units_by_product_type_is_tracked_correctly(): void
    {
        $this->createOrder(stage: 'sew', quantity: 40, productType: 'jersey',    daysUntilDelivery: 2);
        $this->createOrder(stage: 'sew', quantity: 30, productType: 'tracksuit', daysUntilDelivery: 3);
        $plan = $this->service->buildDailyPlan();

        $units = $plan->sewQueue->unitsByProductType;
        $this->assertSame(40, $units['jersey']);
        $this->assertSame(30, $units['tracksuit']);
        $this->assertSame(70, $plan->sewQueue->totalUnits());
    }

    // ──────────────────────────────────────────────
    // Plan aggregations
    // ──────────────────────────────────────────────

    #[Test]
    public function plan_reports_late_orders_correctly(): void
    {
        $this->createOrder(stage: 'design', quantity: 10, daysUntilDelivery: -2);
        $this->createOrder(stage: 'sew',    quantity: 10, daysUntilDelivery:  3);

        $this->assertCount(1, $this->service->buildDailyPlan()->lateOrders());
    }

    #[Test]
    public function plan_reports_both_overtime_departments(): void
    {
        // Print: 420 jerseys at 300/day = 1.4 → overtime
        $this->createOrder(stage: 'print', quantity: 210, productType: 'jersey', daysUntilDelivery: 3);
        $this->createOrder(stage: 'print', quantity: 210, productType: 'jersey', daysUntilDelivery: 4);

        // Sew: 100 jerseys at 80/day = 1.25 → overtime
        $this->createOrder(stage: 'sew', quantity: 50, productType: 'jersey', daysUntilDelivery: 2);
        $this->createOrder(stage: 'sew', quantity: 50, productType: 'jersey', daysUntilDelivery: 3);

        $plan = $this->service->buildDailyPlan();

        $this->assertTrue($plan->hasAnyOvertime());
        $this->assertCount(2, $plan->overtimeDepartments());
        $this->assertCount(2, $plan->overtimeWarnings());
    }

    // ──────────────────────────────────────────────
    // Factory helper
    // ──────────────────────────────────────────────

    private function createOrder(
        string $stage             = 'design',
        int    $quantity          = 20,
        string $priority          = 'normal',
        int    $daysUntilDelivery = 5,
        string $status            = 'in_progress',
        string $productType       = 'jersey',
    ): Order {
        return Order::create([
            'customer_name'  => 'Test Customer',
            'customer_phone' => '012-345-6789',
            'quantity'       => $quantity,
            'product_type'   => $productType,
            'order_date'     => now()->toDateString(),
            'delivery_date'  => now()->addDays($daysUntilDelivery)->toDateString(),
            'priority'       => $priority,
            'stage'          => $stage,
            'status'         => $status,
            'details'        => null,
            'notes'          => null,
            'created_by'     => $this->manager->id,
        ]);
    }
}
