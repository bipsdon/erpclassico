<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes uploaded profile photos attached to orders that were created
 * more than 3 months ago, and clears the path from the database.
 *
 * Photos tied to URL-only orders (profile_photo_path = null) are not affected.
 *
 * Schedule: runs daily (see routes/console.php).
 */
class PruneProfilePhotos extends Command
{
    protected $signature   = 'orders:prune-profile-photos {--dry-run : List files that would be deleted without deleting them}';
    protected $description = 'Delete uploaded profile photos for orders created more than 3 months ago';

    public function handle(): int
    {
        $cutoff   = now()->subMonths(3);
        $dryRun   = $this->option('dry-run');

        $orders = Order::withTrashed()
            ->whereNotNull('profile_photo_path')
            ->where('created_at', '<', $cutoff)
            ->get(['id', 'order_number', 'profile_photo_path', 'created_at']);

        if ($orders->isEmpty()) {
            $this->info('No profile photos to prune.');
            return self::SUCCESS;
        }

        $deleted = 0;
        $missing = 0;

        foreach ($orders as $order) {
            $path = $order->profile_photo_path;

            if ($dryRun) {
                $this->line("[dry-run] Would delete: {$path}  (order {$order->order_number}, created {$order->created_at->toDateString()})");
                $deleted++;
                continue;
            }

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                $deleted++;
            } else {
                $missing++;
            }

            // Clear the path from the DB regardless of whether the file existed,
            // so stale references are cleaned up.
            $order->updateQuietly(['profile_photo_path' => null]);
        }

        if ($dryRun) {
            $this->info("Dry run — {$deleted} file(s) would be deleted.");
        } else {
            $this->info("Pruned {$deleted} profile photo(s). {$missing} path(s) were already missing on disk.");
        }

        return self::SUCCESS;
    }
}
