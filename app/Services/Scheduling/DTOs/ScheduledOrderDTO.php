<?php

namespace App\Services\Scheduling\DTOs;

use App\Models\CapacityConfig;
use App\Models\Order;

/**
 * Represents a single order's slot in a department's daily queue.
 *
 * Immutable value object — constructed by the scheduler, consumed by views/tests.
 */
final class ScheduledOrderDTO
{
    public const STATUS_GREEN  = 'green';
    public const STATUS_YELLOW = 'yellow';
    public const STATUS_RED    = 'red';

    public function __construct(
        public readonly int     $orderId,
        public readonly string  $orderNumber,
        public readonly string  $customerName,
        public readonly ?string $whatsappOrderId,
        public readonly int     $quantity,
        public readonly string  $productType,
        public readonly string  $productTypeLabel,
        public readonly string  $deliveryDate,
        public readonly string  $priority,
        public readonly string  $orderStatus,       // pending | in_progress | completed | on_hold | cancelled
        public readonly string  $department,
        public readonly string  $scheduledDate,
        public readonly bool    $isOvertime,
        public readonly int     $daysUntilDelivery,
        public readonly string  $healthStatus,
        public readonly bool    $isLate,
        public readonly float   $dayFraction,
    ) {}

    public static function fromOrder(
        Order  $order,
        string $department,
        string $scheduledDate,
        bool   $isOvertime,
    ): self {
        $days = (int) now()->startOfDay()
            ->diffInDays(\Illuminate\Support\Carbon::parse($order->delivery_date), false);

        $rate       = CapacityConfig::rateFor($department, $order->product_type);
        $dayFraction = $rate > 0 ? $order->quantity / $rate : 0.0;

        return new self(
            orderId:           $order->id,
            orderNumber:       $order->order_number,
            customerName:      $order->customer_name,
            whatsappOrderId:   $order->whatsapp_order_id,
            quantity:          $order->quantity,
            productType:       $order->product_type,
            productTypeLabel:  $order->product_type_label,
            deliveryDate:      $order->delivery_date->toDateString(),
            priority:          $order->priority,
            orderStatus:       $order->status,
            department:        $department,
            scheduledDate:     $scheduledDate,
            isOvertime:        $isOvertime,
            daysUntilDelivery: $days,
            healthStatus:      self::deriveHealth($order->priority, $days),
            isLate:            $days < 0 && $order->stage !== 'delivered',
            dayFraction:       $dayFraction,
        );
    }

    private static function deriveHealth(string $priority, int $daysRemaining): string
    {
        if ($priority === 'critical') {
            return self::STATUS_RED;
        }

        if ($priority === 'rush') {
            return $daysRemaining <= 1 ? self::STATUS_RED : self::STATUS_YELLOW;
        }

        return match (true) {
            $daysRemaining >= 2 => self::STATUS_GREEN,
            $daysRemaining === 1 => self::STATUS_YELLOW,
            default              => self::STATUS_RED,
        };
    }

    public function healthBadge(): string
    {
        return match ($this->healthStatus) {
            self::STATUS_GREEN  => 'success',
            self::STATUS_YELLOW => 'warning',
            self::STATUS_RED    => 'danger',
        };
    }

    public function priorityBadge(): string
    {
        return match ($this->priority) {
            'critical' => 'danger',
            'rush'     => 'warning',
            default    => 'secondary',
        };
    }

    /**
     * Percentage of one working day this single order consumes (rounded, no cap).
     */
    public function dayPercent(): int
    {
        return (int) round($this->dayFraction * 100);
    }
}
