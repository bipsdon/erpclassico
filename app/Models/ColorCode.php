<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ColorCode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'cyan',
        'magenta',
        'yellow',
        'black',
        'status',
        'created_by',
        'approved_by',
        'pending_name',
        'pending_cyan',
        'pending_magenta',
        'pending_yellow',
        'pending_black',
    ];

    protected function casts(): array
    {
        return [
            'cyan'            => 'integer',
            'magenta'         => 'integer',
            'yellow'          => 'integer',
            'black'           => 'integer',
            'pending_cyan'    => 'integer',
            'pending_magenta' => 'integer',
            'pending_yellow'  => 'integer',
            'pending_black'   => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    /**
     * Convert CMYK (0-100 each) to an RGB hex string for CSS.
     * Formula: R = 255 × (1 - C/100) × (1 - K/100), etc.
     */
    public function getHexAttribute(): string
    {
        return self::cmykToHex($this->cyan, $this->magenta, $this->yellow, $this->black);
    }

    /**
     * Hex for the pending proposed values (edit requests).
     */
    public function getPendingHexAttribute(): ?string
    {
        if ($this->pending_cyan === null) {
            return null;
        }

        return self::cmykToHex(
            $this->pending_cyan,
            $this->pending_magenta,
            $this->pending_yellow,
            $this->pending_black,
        );
    }

    /**
     * Whether this record has any pending designer request.
     */
    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, ['pending_add', 'pending_edit', 'pending_delete'], true);
    }

    /**
     * Human label for the current status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'          => 'Active',
            'pending_add'     => 'Pending Add',
            'pending_edit'    => 'Pending Edit',
            'pending_delete'  => 'Pending Delete',
            default           => ucfirst($this->status),
        };
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    /** Only approved / live color codes. */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /** Records waiting for pipeline manager approval. */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending_add', 'pending_edit', 'pending_delete']);
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ──────────────────────────────────────────────
    // Static helpers
    // ──────────────────────────────────────────────

    public static function cmykToHex(int $c, int $m, int $y, int $k): string
    {
        $r = (int) round(255 * (1 - $c / 100) * (1 - $k / 100));
        $g = (int) round(255 * (1 - $m / 100) * (1 - $k / 100));
        $b = (int) round(255 * (1 - $y / 100) * (1 - $k / 100));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
