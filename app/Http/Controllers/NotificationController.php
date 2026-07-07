<?php

namespace App\Http\Controllers;

use App\Models\PipelineNotification;
use App\Models\PipelineNotificationRead;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // ──────────────────────────────────────────────
    // Pipeline manager: compose & send
    // ──────────────────────────────────────────────

    public function create(): View
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        $sent = PipelineNotification::with(['sender', 'replies.sender'])
            ->whereNull('reply_to_id')          // root messages only in the sent log
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

        $notifications = PipelineNotification::with(['sender', 'reads', 'replies.sender'])
            ->whereNull('reply_to_id')          // only show root messages in inbox
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
    // Reply (department → pipeline manager)
    // ──────────────────────────────────────────────

    public function reply(Request $request, PipelineNotification $notification): RedirectResponse
    {
        $user = auth()->user();

        // Only dept users can reply (PM sends via create form)
        abort_if($user->isPipelineManager(), 403, 'Pipeline managers use the Send Notification form.');

        // Dept users can only reply to notifications they can see
        abort_unless($notification->isVisibleTo($user), 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // Reply is always directed to pipeline_manager
        PipelineNotification::create([
            'sent_by'           => $user->id,
            'reply_to_id'       => $notification->id,
            'target_department' => 'pipeline_manager',
            'subject'           => 'Re: ' . $notification->subject,
            'message'           => $data['message'],
        ]);

        // Auto-mark the original as read
        PipelineNotificationRead::firstOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $user->id],
            ['read_at' => now()],
        );

        return back()->with('success', 'Reply sent to Pipeline Manager.');
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
        $user  = auth()->user();

        // Count unread root messages + unread replies directed at this user's role
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

    private function visibilityScope(\Illuminate\Database\Eloquent\Builder $q, User $user): void
    {
        if ($user->isPipelineManager()) {
            // PM sees everything: broadcasts, all dept-targeted, and PM-targeted (replies)
            return;
        }

        // Department users: broadcasts + messages targeted at their role
        // Replies targeting 'pipeline_manager' are not shown to dept users
        $q->whereNull('target_department')
          ->orWhere('target_department', $user->role);
    }
}
