<?php

namespace Tests\Unit\Scheduling;

use App\Services\Scheduling\DTOs\ScheduledOrderDTO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ScheduledOrderDTOTest extends TestCase
{
    // ──────────────────────────────────────────────
    // Health status derivation
    // ──────────────────────────────────────────────

    #[Test]
    #[DataProvider('healthStatusProvider')]
    public function it_derives_correct_health_status(
        string $priority,
        int    $daysRemaining,
        string $expectedStatus,
    ): void {
        $this->assertSame($expectedStatus, $this->makeDTO($priority, $daysRemaining)->healthStatus);
    }

    public static function healthStatusProvider(): array
    {
        return [
            'critical with 10 days' => ['critical', 10, ScheduledOrderDTO::STATUS_RED],
            'critical with 2 days'  => ['critical', 2,  ScheduledOrderDTO::STATUS_RED],
            'critical with 0 days'  => ['critical', 0,  ScheduledOrderDTO::STATUS_RED],
            'critical overdue'      => ['critical', -3, ScheduledOrderDTO::STATUS_RED],
            'rush with 5 days'      => ['rush', 5, ScheduledOrderDTO::STATUS_YELLOW],
            'rush with 2 days'      => ['rush', 2, ScheduledOrderDTO::STATUS_YELLOW],
            'rush with 1 day'       => ['rush', 1, ScheduledOrderDTO::STATUS_RED],
            'rush with 0 days'      => ['rush', 0, ScheduledOrderDTO::STATUS_RED],
            'rush overdue'          => ['rush', -1, ScheduledOrderDTO::STATUS_RED],
            'normal with 5 days'    => ['normal', 5, ScheduledOrderDTO::STATUS_GREEN],
            'normal with 2 days'    => ['normal', 2, ScheduledOrderDTO::STATUS_GREEN],
            'normal with 1 day'     => ['normal', 1, ScheduledOrderDTO::STATUS_YELLOW],
            'normal with 0 days'    => ['normal', 0, ScheduledOrderDTO::STATUS_RED],
            'normal overdue'        => ['normal', -2, ScheduledOrderDTO::STATUS_RED],
        ];
    }

    // ──────────────────────────────────────────────
    // Badge helpers
    // ──────────────────────────────────────────────

    #[Test]
    public function green_health_maps_to_success_badge(): void
    {
        $this->assertSame('success', $this->makeDTO('normal', 5)->healthBadge());
    }

    #[Test]
    public function yellow_health_maps_to_warning_badge(): void
    {
        $this->assertSame('warning', $this->makeDTO('normal', 1)->healthBadge());
    }

    #[Test]
    public function red_health_maps_to_danger_badge(): void
    {
        $this->assertSame('danger', $this->makeDTO('critical', 5)->healthBadge());
    }

    #[Test]
    #[DataProvider('priorityBadgeProvider')]
    public function it_returns_correct_priority_badge(string $priority, string $expected): void
    {
        $this->assertSame($expected, $this->makeDTO($priority, 5)->priorityBadge());
    }

    public static function priorityBadgeProvider(): array
    {
        return [
            'critical → danger'  => ['critical', 'danger'],
            'rush → warning'     => ['rush',     'warning'],
            'normal → secondary' => ['normal',   'secondary'],
        ];
    }

    // ──────────────────────────────────────────────
    // Day fraction
    // ──────────────────────────────────────────────

    #[Test]
    public function day_fraction_is_correct_for_given_rate(): void
    {
        // 40 jerseys at 80/day = 0.50
        $dto = $this->makeDTO('normal', 5, quantity: 40, dayFraction: 0.5);
        $this->assertEqualsWithDelta(0.5, $dto->dayFraction, 0.001);
        $this->assertSame(50, $dto->dayPercent());
    }

    #[Test]
    public function day_fraction_can_exceed_one_for_overtime(): void
    {
        // 100 jerseys at 80/day = 1.25 (25% overtime)
        $dto = $this->makeDTO('normal', 5, quantity: 100, dayFraction: 1.25);
        $this->assertEqualsWithDelta(1.25, $dto->dayFraction, 0.001);
        $this->assertSame(125, $dto->dayPercent());
    }

    // ──────────────────────────────────────────────
    // Properties & immutability
    // ──────────────────────────────────────────────

    #[Test]
    public function dto_properties_are_readonly(): void
    {
        $dto = $this->makeDTO('normal', 3);

        $this->assertSame(1, $dto->orderId);
        $this->assertSame('ORD-202606-0001', $dto->orderNumber);
        $this->assertSame('Test Customer', $dto->customerName);
        $this->assertSame(22, $dto->quantity);
        $this->assertSame('jersey', $dto->productType);
        $this->assertSame('Jersey', $dto->productTypeLabel);
        $this->assertSame('design', $dto->department);
        $this->assertFalse($dto->isOvertime);
        $this->assertFalse($dto->isLate);
    }

    #[Test]
    public function overdue_order_is_marked_late(): void
    {
        $this->assertTrue($this->makeDTO('normal', -2, isLate: true)->isLate);
    }

    #[Test]
    public function on_time_order_is_not_late(): void
    {
        $this->assertFalse($this->makeDTO('normal', 3)->isLate);
    }

    // ──────────────────────────────────────────────
    // Helper
    // ──────────────────────────────────────────────

    private function makeDTO(
        string $priority,
        int    $daysUntilDelivery,
        bool   $isLate      = false,
        int    $quantity     = 22,
        float  $dayFraction  = 0.275,   // 22/80
    ): ScheduledOrderDTO {
        return new ScheduledOrderDTO(
            orderId:           1,
            orderNumber:       'ORD-202606-0001',
            customerName:      'Test Customer',
            quantity:          $quantity,
            productType:       'jersey',
            productTypeLabel:  'Jersey',
            deliveryDate:      now()->addDays($daysUntilDelivery)->toDateString(),
            priority:          $priority,
            department:        'design',
            scheduledDate:     now()->toDateString(),
            isOvertime:        false,
            daysUntilDelivery: $daysUntilDelivery,
            healthStatus:      $this->resolveHealth($priority, $daysUntilDelivery),
            isLate:            $isLate,
            dayFraction:       $dayFraction,
        );
    }

    private function resolveHealth(string $priority, int $days): string
    {
        if ($priority === 'critical') {
            return ScheduledOrderDTO::STATUS_RED;
        }
        if ($priority === 'rush') {
            return $days <= 1 ? ScheduledOrderDTO::STATUS_RED : ScheduledOrderDTO::STATUS_YELLOW;
        }

        return match (true) {
            $days >= 2  => ScheduledOrderDTO::STATUS_GREEN,
            $days === 1 => ScheduledOrderDTO::STATUS_YELLOW,
            default     => ScheduledOrderDTO::STATUS_RED,
        };
    }
}
