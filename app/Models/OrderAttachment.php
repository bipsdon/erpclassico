<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrderAttachment extends Model
{
    protected $fillable = [
        'order_id',
        'original_name',
        'stored_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    /**
     * Human-readable file size (e.g. "2.4 MB").
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }

    /**
     * Bootstrap icon class based on MIME type.
     */
    public function getFileIconAttribute(): string
    {
        return match (true) {
            str_contains($this->mime_type, 'pdf')                => 'bi-file-earmark-pdf',
            str_contains($this->mime_type, 'image')              => 'bi-file-earmark-image',
            str_contains($this->mime_type, 'zip')                => 'bi-file-earmark-zip',
            str_contains($this->mime_type, 'postscript')         => 'bi-file-earmark-code', // AI/EPS
            default                                              => 'bi-file-earmark',
        };
    }

    /**
     * Whether this attachment is an image previewable in-browser.
     */
    public function getIsImageAttribute(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
