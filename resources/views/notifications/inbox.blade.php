@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title')
    <i class="bi bi-bell me-2 text-primary"></i>Notifications
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted" style="font-size:.85rem">
        {{ $notifications->total() }} notification(s)
    </div>
    @if($notifications->isNotEmpty())
        <form method="POST" action="{{ route('notifications.mark-all-read') }}">
            @csrf
            <button class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-check2-all me-1"></i> Mark all as read
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
            <div class="card shadow-sm border-0 {{ $isRead ? '' : 'border-start border-primary border-3' }}"
                 id="notification-{{ $n->id }}">
                <div class="card-body px-4 py-3">
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
                        @if(! $isRead)
                            <button class="btn btn-sm btn-outline-secondary flex-shrink-0 mark-read-btn"
                                    data-id="{{ $n->id }}"
                                    data-url="{{ route('notifications.mark-read', $n) }}"
                                    title="Mark as read">
                                <i class="bi bi-check2"></i>
                            </button>
                        @else
                            <i class="bi bi-check2-all text-success flex-shrink-0 mt-1" title="Read"></i>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
@endif

@endsection

@push('scripts')
<script>
document.querySelectorAll('.mark-read-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
        const id  = this.dataset.id;
        const url = this.dataset.url;

        try {
            await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });

            // Update UI: remove NEW badge, remove blue border, swap button to checkmark
            const card = document.getElementById('notification-' + id);
            card.classList.remove('border-start', 'border-primary', 'border-3');
            const badge = card.querySelector('.badge.bg-primary');
            if (badge && badge.textContent.trim() === 'NEW') badge.remove();
            this.outerHTML = '<i class="bi bi-check2-all text-success flex-shrink-0 mt-1"></i>';

            // Update topbar badge
            updateNotifBadge();
        } catch (e) {
            console.error(e);
        }
    });
});

async function updateNotifBadge() {
    try {
        const res  = await fetch('{{ route("notifications.unread-count") }}', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        const badge = document.getElementById('notif-badge');
        if (badge) {
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? '' : 'none';
        }
    } catch (e) {}
}
</script>
@endpush
