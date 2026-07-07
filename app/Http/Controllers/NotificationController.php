<?php

namespace App\Http\Controllers;

use App\Models\PipelineNotification;
use App\Models\PipelineNotificationRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // ──────────────────────────────────────────────
    // Pipeline manager: send notifications
    // ──────────────────────────────────────────────

    public function create(): View
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        $sent = PipelineNotification::with('sender')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('notifications.create', compact('sent'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        $data = $request->validate([
            'target_department' => ['nullable', 'in:designer,printing_manager,sewing_manager'],
            'subject'           => ['required', 'string', 'max:200'],
            'message'           => ['required', 'string', 'max:2000'],
        ]);

        PipelineNotification::create([
            'sent_by'           => auth()->id(),
            'target_department' => $data['target_department'] ?: null,
            'subject'           => $data['subject'],
            'message'           => $data['message'],
        ]);

        $target = $data['target_department']
            ? match ($data['target_department']) {
                'designer'         => 'Design Team',
                'printing_manager' => 'Printing Team',
                'sewing_manager'   => 'Sewing Team',
            }
            : 'All Departments';

        return back()->with('success', "Notification sent to {$target}.");
    }

    // ──────────────────────────────────────────────
    // All roles: inbox
    // ──────────────────────────────────────────────

    public function inbox(): View
    {
        $user = auth()->user();

        $notifications = PipelineNotification::with(['sender', 'reads'])
            ->where(function ($q) use ($user) {
                $this->visibilityScope($q, $user);
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        $readIds = PipelineNotificationRead::where('user_id', $user->id)
            ->pluck('notification_id')
            ->flip();

        return view('notifications.inbox', compact('notifications', 'readIds'));
    }

    // ──────────────────────────────────────────────
    // Mark as read (AJAX or redirect)
    // ──────────────────────────────────────────────

    public function markRead(PipelineNotification $notification): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        PipelineNotificationRead::firstOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $user->id],
            ['read_at' => now()],
        );

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        $user = auth()->user();

        $ids = PipelineNotification::where(function ($q) use ($user) {
            $this->visibilityScope($q, $user);
        })->pluck('id');

        foreach ($ids as $id) {
            PipelineNotificationRead::firstOrCreate(
                ['notification_id' => $id, 'user_id' => $user->id],
                ['read_at' => now()],
            );
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    // ──────────────────────────────────────────────
    // Badge count (AJAX)
    // ──────────────────────────────────────────────

    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();

        $count = PipelineNotification::where(function ($q) use ($user) {
            $this->visibilityScope($q, $user);
        })
        ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
        ->count();

        return response()->json(['count' => $count]);
    }

    // ──────────────────────────────────────────────
    // Shared visibility scope
    // ──────────────────────────────────────────────

    /**
     * Apply a WHERE clause that filters notifications visible to $user.
     *
     * Rules:
     *  - null target (broadcast)       → visible to everyone
     *  - 'pipeline_manager' target     → visible to pipeline_manager only
     *  - 'designer' / 'printing_...'   → visible to that role + pipeline_manager
     *  - pipeline_manager user         → sees everything
     */
    private function visibilityScope(\Illuminate\Database\Eloquent\Builder $q, \App\Models\User $user): void
    {
        if ($user->isPipelineManager()) {
            // PM sees all: broadcasts, dept-targeted, and PM-targeted
            $q->whereNull('target_department')
              ->orWhereNotNull('target_department');
            return;
        }

        // Department users see: broadcasts + notifications targeted at their role
        // They do NOT see 'pipeline_manager'-targeted notifications
        $q->whereNull('target_department')
          ->orWhere('target_department', $user->role);
    }
}
