<?php

namespace App\Models;

use App\Models\CapacityConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'whatsapp_order_id',
        'quantity',
        'product_type',
        'order_date',
        'delivery_date',
        'priority',
        'stage',
        'pipeline',
        'status',
        'details',
        'notes',
        'created_by',
    ];

    // ──────────────────────────────────────────────
    // Casts
    // ──────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'order_date'    => 'date',
            'delivery_date' => 'date',
            'quantity'      => 'integer',
            'pipeline'      => 'array',
        ];
    }

    // ──────────────────────────────────────────────
    // Accessors / computed properties
    // ──────────────────────────────────────────────

    /**
     * Human-readable product type label.
     */
    public function getProductTypeLabelAttribute(): string
    {
        return CapacityConfig::productTypes()[$this->product_type] ?? ucfirst($this->product_type);
    }

    /**
     * True when delivery_date is strictly before today and order is not yet delivered.
     * An order due today is not considered late.
     */
    public function getIsLateAttribute(): bool
    {
        return $this->delivery_date->copy()->startOfDay()->lt(now()->startOfDay())
            && $this->stage !== 'delivered';
    }

    /**
     * Human-readable stage label.
     */
    public function getStageLabelAttribute(): string
    {
        return match ($this->stage) {
            'design'    => 'Design',
            'print'     => 'Print',
            'sew'       => 'Sew',
            'ready'     => 'Ready for Delivery',
            'delivered' => 'Delivered',
            default     => ucfirst($this->stage),
        };
    }

    /**
     * Badge colour class (Bootstrap) for priority.
     */
    public function getPriorityBadgeAttribute(): string
    {
        return match ($this->priority) {
            'rush'     => 'warning',
            'critical' => 'danger',
            default    => 'secondary',
        };
    }

    /**
     * Badge colour class (Bootstrap) for status.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'     => 'secondary',
            'in_progress' => 'primary',
            'completed'   => 'success',
            'on_hold'     => 'warning',
            'cancelled'   => 'danger',
            default       => 'light',
        };
    }

    /**
     * The timestamp when the order was actually delivered (stage set to 'delivered').
     * Returns null if not yet delivered.
     */
    public function getDeliveredAtAttribute(): ?\Illuminate\Support\Carbon
    {
        $log = $this->stageLogs->first(fn ($l) => $l->to_stage === 'delivered');

        return $log?->created_at;
    }

    /**
     * True when the order was delivered but after its promised delivery_date.
     */
    public function getWasDeliveredLateAttribute(): bool
    {
        if ($this->stage !== 'delivered' || ! $this->delivered_at) {
            return false;
        }

        return $this->delivered_at->copy()->startOfDay()
            ->gt($this->delivery_date->copy()->startOfDay());
    }

    /**
     * How many days late the delivery was (0 if on time or not yet delivered).
     */
    public function getDaysDeliveredLateAttribute(): int
    {
        if (! $this->was_delivered_late) {
            return 0;
        }

        return (int) $this->delivery_date->copy()->startOfDay()
            ->diffInDays($this->delivered_at->copy()->startOfDay());
    }

    /**
     * Days remaining until delivery (negative = overdue).
     */
    public function getDaysRemainingAttribute(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->delivery_date, false);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    /** Orders currently in a given department stage. */
    public function scopeInStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    /** Active (not cancelled/delivered) orders. */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled'])
                     ->where('stage', '!=', 'delivered');
    }

    /** Rush and critical orders — always floated to top. */
    public function scopeRush($query)
    {
        return $query->whereIn('priority', ['rush', 'critical']);
    }

    /** Orders past their delivery date that are not yet delivered. */
    public function scopeLate($query)
    {
        return $query->where('delivery_date', '<', now()->toDateString())
                     ->where('stage', '!=', 'delivered');
    }

    /**
     * Standard priority sort: critical → rush → normal, then earliest delivery first.
     */
    public function scopePriorityOrdered($query)
    {
        return $query->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'rush' THEN 1 ELSE 2 END")
                     ->orderBy('delivery_date');
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function players(): HasMany
    {
        return $this->hasMany(OrderPlayer::class)->orderBy('sort_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OrderAttachment::class);
    }

    public function productionSchedules(): HasMany
    {
        return $this->hasMany(ProductionSchedule::class);
    }

    public function stageLogs(): HasMany
    {
        return $this->hasMany(OrderStageLog::class)->latest('created_at');
    }

    // ──────────────────────────────────────────────
    // Pipeline helpers
    // ──────────────────────────────────────────────

    /**
     * The ordered list of stages this order goes through.
     * Falls back to the full pipeline if the column is null (legacy rows).
     *
     * @return string[]
     */
    public function effectivePipeline(): array
    {
        return $this->pipeline ?? ['design', 'print', 'sew'];
    }

    /**
     * The stage that comes after $current in this order's pipeline.
     * Returns 'ready' when $current is the last production stage,
     * or null if $current is not in the pipeline at all.
     */
    public function nextStage(string $current): ?string
    {
        $stages = $this->effectivePipeline();
        $index  = array_search($current, $stages, true);

        if ($index === false) {
            return null;
        }

        // If there is a next stage in the pipeline, return it; otherwise 'ready'.
        return $stages[$index + 1] ?? 'ready';
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /** Schedule slot for a specific department. */
    public function scheduleFor(string $department): ?ProductionSchedule
    {
        return $this->productionSchedules
            ->firstWhere('department', $department);
    }

    // ──────────────────────────────────────────────
    // Booted / lifecycle hooks
    // ──────────────────────────────────────────────

    /**
     * Auto-generate order number: ORD-YYYYMM-XXXX
     * Called before the model is created.
     */
    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                // Lock the last order row for this prefix so concurrent inserts
                // wait in line instead of racing to read the same sequence number.
                $prefix = 'ORD-' . now()->format('Ym') . '-';

                \Illuminate\Support\Facades\DB::transaction(function () use ($order, $prefix) {
                    $last = static::withTrashed()
                        ->where('order_number', 'like', $prefix . '%')
                        ->lockForUpdate()
                        ->orderByDesc('id')
                        ->value('order_number');

                    $sequence = $last
                        ? ((int) substr($last, -4)) + 1
                        : 1;

                    $order->order_number = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
                });
            }
        });
    }
}
