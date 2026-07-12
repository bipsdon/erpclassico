@extends('layouts.app')

@section('title', $order->whatsapp_order_id ?? $order->order_number)
@section('page-title')
    <i class="bi bi-receipt me-2 text-primary"></i>{{ $order->whatsapp_order_id ?? $order->order_number }}
@endsection

@section('content')

{{-- ── Back + action buttons ─────────────────────────────── --}}
<div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>All Orders
    </a>

    {{-- Export buttons — available to all authenticated users --}}
    <a href="{{ route('orders.export.pdf', $order) }}" class="btn btn-sm btn-outline-danger">
        <i class="bi bi-file-pdf me-1"></i>Export PDF
    </a>
    @if($order->players->isNotEmpty())
        <a href="{{ route('orders.export.xlsx', $order) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Name/Number XLSX
        </a>
    @endif

    @if(auth()->user()->isPipelineManager())
        {{-- Mark as Delivered — only when order is Ready --}}
        @if($order->stage === 'ready')
            <form method="POST"
                  action="{{ route('production.deliver', $order) }}"
                  onsubmit="return confirm('Mark {{ $order->whatsapp_order_id ?? $order->order_number }} as delivered to the customer?')">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-success">
                    <i class="bi bi-truck me-1"></i>Mark as Delivered
                </button>
            </form>
        @endif
        <a href="{{ route('orders.edit', $order) }}" class="btn btn-sm btn-primary ms-auto">
            <i class="bi bi-pencil me-1"></i>Edit Order
        </a>
        <form method="POST"
              action="{{ route('orders.duplicate', $order) }}"
              onsubmit="return confirm('Duplicate {{ addslashes($order->whatsapp_order_id ?? $order->order_number) }}? A new order will be created in the Design queue with the same details.')">
            @csrf
            <button class="btn btn-sm btn-outline-primary">
                <i class="bi bi-copy me-1"></i>Duplicate
            </button>
        </form>
        <form method="POST"
              action="{{ route('orders.destroy', $order) }}"
              onsubmit="return confirm('Delete {{ $order->whatsapp_order_id ?? $order->order_number }}?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash me-1"></i>Delete
            </button>
        </form>
    @endif
</div>

<div class="row g-4">

    {{-- ── Left column: order details ─────────────────────── --}}
    <div class="col-12 col-xl-8">

        {{-- Header card --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-center">

                    <div class="col-12 col-sm-6">
                        <div class="text-muted small mb-1">WhatsApp Order ID</div>
                        <div class="fw-bold fs-5">{{ $order->whatsapp_order_id ?? '—' }}</div>
                        <div class="text-muted small">Ref: {{ $order->order_number }}</div>
                    </div>

                    <div class="col-6 col-sm-3 text-sm-center">
                        <div class="text-muted small mb-1">Stage</div>
                        <span class="badge bg-secondary fs-6">{{ $order->stage_label }}</span>
                    </div>

                    <div class="col-6 col-sm-3 text-sm-end">
                        <div class="text-muted small mb-1">Status</div>
                        <span class="badge bg-{{ $order->status_badge }} fs-6">
                            {{ ucwords(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </div>

                    <div class="col-12"><hr class="my-1"></div>

                    <div class="col-6 col-md-3">
                        <div class="text-muted small">Customer</div>
                        <div class="fw-semibold">{{ $order->customer_name }}</div>
                        <div class="text-muted small">{{ $order->customer_phone }}</div>
                    </div>

                    <div class="col-6 col-md-2 text-center">
                        <div class="text-muted small">Quantity</div>
                        <div class="fw-bold fs-4 text-primary">{{ number_format($order->quantity) }}</div>
                        <div class="text-muted small">{{ $order->product_type_label }}</div>
                    </div>

                    <div class="col-6 col-md-2 text-center">
                        <div class="text-muted small">Priority</div>
                        <span class="badge bg-{{ $order->priority_badge }} fs-6 mt-1">
                            {{ ucfirst($order->priority) }}
                        </span>
                    </div>

                    <div class="col-6 col-md-2 text-center">
                        <div class="text-muted small">Order Date</div>
                        <div class="fw-semibold">{{ $order->order_date->format('d M Y') }}</div>
                    </div>

                    <div class="col-6 col-md-3 text-center">
                        <div class="text-muted small">Delivery Date</div>
                        <div class="fw-semibold {{ $order->is_late ? 'text-danger' : '' }}">
                            {{ $order->delivery_date->format('d M Y') }}
                        </div>
                        @if($order->stage === 'delivered')
                            @if($order->was_delivered_late)
                                <span class="days-chip mt-1 bg-danger text-white">Delivered {{ $order->days_delivered_late }}d late</span>
                            @else
                                <span class="days-chip mt-1 bg-success text-white">Delivered on time</span>
                            @endif
                        @elseif($order->stage === 'ready')
                            <span class="days-chip mt-1 bg-info text-white">Ready</span>
                        @elseif($order->days_remaining < 0)
                            <span class="days-chip mt-1 bg-danger text-white">{{ abs($order->days_remaining) }} day(s) late</span>
                        @elseif($order->days_remaining === 0)
                            <span class="days-chip mt-1 bg-warning text-dark">Due today</span>
                        @else
                            <span class="days-chip mt-1 bg-light border text-secondary">{{ $order->days_remaining }} day(s) remaining</span>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        {{-- Production pipeline progress --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 fw-semibold">
                <i class="bi bi-diagram-3 me-2 text-primary"></i>Production Pipeline
            </div>
            <div class="card-body py-4">
                <div class="d-flex align-items-center justify-content-between position-relative">
                    {{-- connector line --}}
                    <div class="position-absolute w-100 top-50 translate-middle-y"
                         style="height:3px;background:#dee2e6;z-index:0;left:0"></div>

                    @php
                        $stageOrder = ['design' => 0, 'print' => 1, 'sew' => 2, 'ready' => 3, 'delivered' => 4];
                        $currentIdx = $stageOrder[$order->stage] ?? 0;
                        $steps = [
                            ['key' => 'design',    'label' => 'Design',    'icon' => 'bi-pencil-square'],
                            ['key' => 'print',     'label' => 'Print',     'icon' => 'bi-printer'],
                            ['key' => 'sew',       'label' => 'Sewing',    'icon' => 'bi-scissors'],
                            ['key' => 'ready',     'label' => 'Ready',     'icon' => 'bi-box-seam'],
                            ['key' => 'delivered', 'label' => 'Delivered', 'icon' => 'bi-truck'],
                        ];
                    @endphp

                    @foreach($steps as $step)
                        @php
                            $idx  = $stageOrder[$step['key']];
                            $done = $idx < $currentIdx;
                            $now  = $idx === $currentIdx;
                        @endphp
                        <div class="d-flex flex-column align-items-center flex-fill" style="z-index:1">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mb-2
                                {{ $done ? 'bg-success text-white' : ($now ? 'bg-primary text-white' : 'bg-white border-2 border text-muted') }}"
                                 style="width:44px;height:44px;border-width:2px!important">
                                @if($done)
                                    <i class="bi bi-check2-all fs-5"></i>
                                @else
                                    <i class="bi {{ $step['icon'] }}"></i>
                                @endif
                            </div>
                            <span style="font-size:.72rem;font-weight:{{ $now ? '700' : '400' }}"
                                  class="{{ $now ? 'text-primary' : ($done ? 'text-success' : 'text-muted') }}">
                                {{ $step['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Order Details (rich text) --}}
        @if($order->details)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 fw-semibold">
                    <i class="bi bi-file-text me-2 text-primary"></i>Order Specifications
                </div>
                <div class="card-body prose" style="line-height:1.7">
                    <style>
                        .prose a { color: #0d6efd; text-decoration: underline; }
                        .prose a:hover { color: #0a58ca; }
                    </style>
                    @php
                        // Auto-linkify any bare URLs in the stored HTML that aren't already inside an <a> tag
                        $details = $order->details;
                        if ($details) {
                            $details = preg_replace(
                                '/(?<!href=["\'])(?<!src=["\'])(https?:\/\/[^\s<>"\']+)/i',
                                '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
                                $details
                            );
                        }
                    @endphp
                    {!! $details !!}
                </div>
            </div>
        @endif

        {{-- Internal Notes --}}
        @if($order->notes)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 fw-semibold">
                    <i class="bi bi-sticky me-2 text-warning"></i>Internal Notes
                </div>
                <div class="card-body text-secondary" style="white-space:pre-wrap;font-size:.9rem">{{ $order->notes }}</div>
            </div>
        @endif

        {{-- Players list --}}
        @if($order->players->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                    <i class="bi bi-people me-1 text-primary"></i>
                    <span class="fw-semibold">Name & Number List</span>
                    <span class="badge bg-primary rounded-pill ms-1">{{ $order->players->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table id="players-table" class="table table-sm table-hover mb-0 align-middle" data-sortable>
                        <thead class="table-light queue-table">
                            <tr>
                                <th class="ps-3 sort-th" data-col="0" data-default="asc">#</th>
                                <th class="sort-th" data-col="1">Player Name</th>
                                <th class="text-center sort-th" data-col="2">Jersey #</th>
                                <th class="text-center sort-th" data-col="3">Size</th>
                                <th class="sort-th" data-col="4">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->players as $player)
                                <tr>
                                    <td class="ps-3 text-muted" data-val="{{ $loop->iteration }}">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold" data-val="{{ $player->player_name }}">{{ $player->player_name }}</td>
                                    <td class="text-center" data-val="{{ $player->jersey_number }}">
                                        <span class="badge bg-dark fs-6">{{ $player->jersey_number }}</span>
                                    </td>
                                    <td class="text-center" data-val="{{ $player->size ?? '' }}">
                                        @if($player->size)
                                            <span class="badge bg-light text-dark border">{{ $player->size }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-muted" data-val="{{ $player->notes ?? '' }}" style="font-size:.85rem">{{ $player->notes ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Attachments --}}
        @if($order->attachments->isNotEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
                    <i class="bi bi-paperclip text-primary"></i>
                    <span class="fw-semibold">Guidelines & Attachments</span>
                    <span class="badge bg-secondary rounded-pill ms-1">{{ $order->attachments->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($order->attachments as $attachment)
                            <div class="col-12 col-sm-6">
                                <div class="d-flex align-items-center gap-2 p-2 border rounded">
                                    <i class="bi {{ $attachment->file_icon }} fs-4 text-secondary flex-shrink-0"></i>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="text-truncate fw-semibold" style="font-size:.85rem">
                                            {{ $attachment->original_name }}
                                        </div>
                                        <div class="text-muted" style="font-size:.75rem">
                                            {{ $attachment->file_size_human }}
                                            · Uploaded by {{ $attachment->uploader->name ?? 'Unknown' }}
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <a href="{{ route('orders.attachments.download', [$order, $attachment]) }}"
                                           class="btn btn-sm btn-outline-secondary"
                                           title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        @if(auth()->user()->isPipelineManager())
                                            <form method="POST"
                                                  action="{{ route('orders.attachments.delete', [$order, $attachment]) }}"
                                                  onsubmit="return confirm('Delete this attachment?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- ── Right column: timeline ───────────────────────── --}}
    <div class="col-12 col-xl-4">

        {{-- Production schedule --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 fw-semibold">
                <i class="bi bi-calendar-check me-2 text-primary"></i>Production Schedule
            </div>
            <ul class="list-group list-group-flush">
                @php
                    $deptRoles = [
                        'design' => 'designer',
                        'print'  => 'printing_manager',
                        'sew'    => 'sewing_manager',
                    ];
                    $authUser = auth()->user();
                @endphp
                @foreach(['design' => 'Design', 'print' => 'Print', 'sew' => 'Sewing'] as $dept => $label)
                    @php
                        $slot        = $order->productionSchedules->firstWhere('department', $dept);
                        $canAct      = $authUser->isPipelineManager() || $authUser->role === $deptRoles[$dept];
                        $isActive    = $order->stage === $dept;
                        $isInProgress = $isActive && $order->status === 'in_progress';
                    @endphp
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <span class="fw-semibold" style="font-size:.875rem">{{ $label }}</span>
                            </div>
                            <div class="text-end">
                                @if($slot && $slot->completed_at)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check2 me-1"></i>Done
                                    </span>
                                    <div class="text-muted mt-1" style="font-size:.7rem">
                                        {{ $slot->completed_at->format('d M Y H:i') }}
                                    </div>
                                @elseif($slot)
                                    <span class="badge bg-primary">
                                        {{ $slot->scheduled_date->format('d M Y') }}
                                    </span>
                                    <div class="text-muted mt-1" style="font-size:.7rem">
                                        {{ number_format($slot->quantity_scheduled) }} {{ $order->product_type_label }}
                                        @if($slot->is_overtime)
                                            <span class="badge bg-danger ms-1">OT</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small">Not scheduled</span>
                                @endif
                            </div>
                        </div>

                        {{-- Action buttons — only for the active stage and authorised roles --}}
                        @if($canAct && $isActive)
                            <div class="d-flex gap-2 mt-2">
                                @if(! $isInProgress)
                                    <form method="POST"
                                          action="{{ route('production.start', [$dept, $order->id]) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-play-fill me-1"></i>Start
                                        </button>
                                    </form>
                                @endif
                                <form method="POST"
                                      action="{{ route('production.complete', [$dept, $order->id]) }}"
                                      onsubmit="return confirm('Mark {{ $label }} as complete for this order?')">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-success">
                                        <i class="bi bi-check2 me-1"></i>Mark Complete
                                    </button>
                                </form>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Stage audit log --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 fw-semibold">
                <i class="bi bi-clock-history me-2 text-secondary"></i>Activity Log
            </div>
            <ul class="list-group list-group-flush">
                @forelse($order->stageLogs as $log)
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                @if($log->from_stage)
                                    <span class="badge bg-secondary">{{ ucfirst($log->from_stage) }}</span>
                                    <i class="bi bi-arrow-right mx-1 text-muted" style="font-size:.7rem"></i>
                                @endif
                                <span class="badge bg-primary">{{ ucfirst($log->to_stage) }}</span>
                                @if($log->notes)
                                    <div class="text-muted mt-1" style="font-size:.75rem">{{ $log->notes }}</div>
                                @endif
                            </div>
                            <div class="text-end text-muted" style="font-size:.72rem">
                                <div>{{ $log->changedBy->name ?? 'System' }}</div>
                                <div>{{ $log->created_at->format('d M H:i') }}</div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted text-center py-3" style="font-size:.85rem">
                        No activity recorded yet
                    </li>
                @endforelse
            </ul>
        </div>

    </div>

</div>

@endsection
