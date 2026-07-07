@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title')
    <i class="bi bi-bell me-2 text-primary"></i>Notifications
@endsection

@push('styles')
<style>
    .notif-card          { transition: box-shadow .15s; }
    .notif-card:hover    { box-shadow: 0 .25rem .75rem rgba(0,0,0,.09) !important; }
    .reply-thread        { border-left: 3px solid #dee2e6; margin-left: 1.5rem; padding-left: 1rem; }
    .reply-bubble        { background: #f8f9fa; border-radius: .5rem; padding: .75rem 1rem; }
    .reply-bubble.own    { background: #e8f4fd; }
    .reply-form-wrap     { margin-left: 1.5rem; padding-left: 1rem; border-left: 3px solid #0d6efd22; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="text-muted" style="font-size:.85rem">
        {{ $notifications->total() }} notification(s)
    </div>
    @if($notifications->isNotEmpty())
        <form method="POST" action="{{ route('notifications.mark-all-read') }}">
            @csrf
            <button class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-check2-all me-1"></i>Mark all as read
            </button>
        </form>
    @endif
</div>

@if($notifications->isEmpty())
    <div class="card shadow-sm border-0">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
            No notifications yet.
        </div>
    </div>
@else
    <div class="d-flex flex-column gap-3">
        @foreach($notifications as $n)
            @php $isRead = isset($readIds[$n->id]); @endphp

            <div class="card shadow-sm border-0 notif-card {{ $isRead ? '' : 'border-start border-primary border-3' }}"
                 id="notification-{{ $n->id }}">
                <div class="card-body px-4 py-3">

                    {{-- ── Header row ──────────────────────────────────────── --}}
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                @if(! $isRead)
                                    <span class="badge bg-primary" style="font-size:.65rem">NEW</span>
                                @endif
                                <span class="badge bg-{{ $n->isBroadcast() ? 'primary' : 'secondary' }}">
                                    {{ $n->target_label }}
                                </span>
                                <span class="fw-semibold" style="font-size:.925rem">{{ $n->subject }}</span>
                            </div>
                            <p class="mb-2" style="font-size:.875rem;white-space:pre-line;color:#374151">
                                {{ $n->message }}
                            </p>
                            <div class="text-muted" style="font-size:.78rem">
                                <i class="bi bi-person me-1"></i>{{ $n->sender->name }}
                                &nbsp;·&nbsp;
                                <i class="bi bi-clock me-1"></i>{{ $n->created_at->format('d M Y, g:i A') }}
                                ({{ $n->created_at->diffForHumans() }})
                            </div>
                        </div>

                        <div class="d-flex flex-column align-items-end gap-2 flex-shrink-0">
                            @if(! $isRead)
                                <button class="btn btn-sm btn-outline-secondary mark-read-btn"
                                        data-id="{{ $n->id }}"
                                        data-url="{{ route('notifications.mark-read', $n) }}"
                                        title="Mark as read">
                                    <i class="bi bi-check2"></i>
                                </button>
                            @else
                                <i class="bi bi-check2-all text-success mt-1" title="Read"></i>
                            @endif

                            {{-- Reply toggle — dept users only --}}
                            @if(! auth()->user()->isPipelineManager())
                                <button class="btn btn-sm btn-outline-primary reply-toggle-btn"
                                        data-target="reply-form-{{ $n->id }}"
                                        title="Reply to Pipeline Manager">
                                    <i class="bi bi-reply me-1"></i>Reply
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- ── Replies thread ──────────────────────────────────── --}}
                    @if($n->replies->isNotEmpty())
                        <div class="reply-thread mt-3">
                            @foreach($n->replies as $reply)
                                @php $replyIsOwn = ($reply->sent_by === auth()->id()); @endphp
                                <div class="reply-bubble {{ $replyIsOwn ? 'own' : '' }} mb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <span class="fw-semibold" style="font-size:.8rem">
                                            <i class="bi bi-person-circle me-1 text-muted"></i>
                                            {{ $reply->sender->name }}
                                            @if($replyIsOwn)
                                                <span class="text-muted fw-normal">(you)</span>
                                            @endif
                                        </span>
                                        <span class="text-muted" style="font-size:.72rem">
                                            {{ $reply->created_at->format('d M, g:i A') }}
                                        </span>
                                    </div>
                                    <p class="mb-0 mt-1" style="font-size:.85rem;white-space:pre-line;color:#374151">
                                        {{ $reply->message }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- ── Reply form (dept users) ─────────────────────────── --}}
                    @if(! auth()->user()->isPipelineManager())
                        <div class="reply-form-wrap mt-3" id="reply-form-{{ $n->id }}" style="display:none">
                            <form method="POST"
                                  action="{{ route('notifications.reply', $n) }}">
                                @csrf
                                <div class="d-flex gap-2 align-items-start">
                                    <textarea name="message"
                                              rows="2"
                                              class="form-control form-control-sm"
                                              placeholder="Write a reply to Pipeline Manager…"
                                              required
                                              style="resize:vertical;font-size:.85rem"></textarea>
                                    <button type="submit" class="btn btn-sm btn-primary flex-shrink-0">
                                        <i class="bi bi-send me-1"></i>Send
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $notifications->links() }}
    </div>
@endif

@endsection

@push('scripts')
<script>
// Mark as read (AJAX)
document.querySelectorAll('.mark-read-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
        const id  = this.dataset.id;
        const url = this.dataset.url;
        try {
            await fetch(url, {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept':       'application/json',
                },
            });
            const card = document.getElementById('notification-' + id);
            card.classList.remove('border-start', 'border-primary', 'border-3');
            const newBadge = card.querySelector('.badge.bg-primary');
            if (newBadge && newBadge.textContent.trim() === 'NEW') newBadge.remove();
            this.replaceWith(Object.assign(document.createElement('i'), {
                className: 'bi bi-check2-all text-success mt-1',
                title:     'Read',
            }));
            updateNotifBadge();
        } catch (e) { console.error(e); }
    });
});

// Toggle reply forms
document.querySelectorAll('.reply-toggle-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const target = document.getElementById(this.dataset.target);
        if (! target) return;
        const visible = target.style.display !== 'none';
        target.style.display = visible ? 'none' : 'block';
        this.innerHTML = visible
            ? '<i class="bi bi-reply me-1"></i>Reply'
            : '<i class="bi bi-x me-1"></i>Cancel';
        if (! visible) target.querySelector('textarea')?.focus();
    });
});

async function updateNotifBadge() {
    try {
        const res  = await fetch('{{ route("notifications.unread-count") }}', {
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        ['notif-badge', 'sidebar-notif-badge'].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.textContent = data.count; el.style.display = data.count > 0 ? '' : 'none'; }
        });
    } catch (e) {}
}
</script>
@endpush
