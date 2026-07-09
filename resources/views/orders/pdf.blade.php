<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

        .page-header { background: #1e3a5f; color: #fff; padding: 20px 24px; margin-bottom: 20px; }
        .page-header h1 { font-size: 20px; font-weight: 700; letter-spacing: .5px; }
        .page-header .sub { font-size: 11px; opacity: .75; margin-top: 4px; }

        .section { margin: 0 24px 16px; }
        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #1e3a5f;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }

        .info-grid { width: 100%; border-collapse: collapse; }
        .info-grid td { padding: 5px 8px; vertical-align: top; width: 25%; }
        .info-grid td.label { color: #666; font-size: 9.5px; text-transform: uppercase; letter-spacing: .5px; }
        .info-grid td.value { font-weight: 600; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9.5px;
            font-weight: 700;
        }
        .badge-danger  { background: #dc3545; color: #fff; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-success { background: #198754; color: #fff; }
        .badge-primary { background: #0d6efd; color: #fff; }
        .badge-secondary { background: #6c757d; color: #fff; }

        /* Pipeline */
        .pipeline { display: table; width: 100%; border-collapse: collapse; }
        .pipeline-step { display: table-cell; text-align: center; padding: 8px 4px; }
        .pipeline-dot {
            width: 32px; height: 32px;
            border-radius: 50%;
            margin: 0 auto 4px;
            line-height: 32px;
            font-size: 11px;
            font-weight: 700;
        }
        .step-done    { background: #198754; color: #fff; }
        .step-current { background: #0d6efd; color: #fff; }
        .step-pending { background: #e9ecef; color: #6c757d; border: 1px solid #dee2e6; }
        .pipeline-label { font-size: 9px; color: #666; }
        .pipeline-label.active { font-weight: 700; color: #0d6efd; }

        /* Players table */
        table.players { width: 100%; border-collapse: collapse; }
        table.players th {
            background: #1e3a5f;
            color: #fff;
            padding: 6px 8px;
            text-align: left;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        table.players th.center, table.players td.center { text-align: center; }
        table.players tr:nth-child(even) td { background: #f8f9fa; }
        table.players td { padding: 5px 8px; border-bottom: 1px solid #dee2e6; font-size: 10.5px; }

        .footer {
            margin: 20px 24px 0;
            padding-top: 8px;
            border-top: 1px solid #dee2e6;
            font-size: 9px;
            color: #999;
            display: table;
            width: calc(100% - 48px);
        }
        .footer-left  { display: table-cell; }
        .footer-right { display: table-cell; text-align: right; }

        .late { color: #dc3545; font-weight: 700; }

        .whatsapp-id { font-size: 10px; color: #25D366; font-weight: 600; }
    </style>
</head>
<body>

{{-- ── Header ──────────────────────────────────────────── --}}
<div class="page-header">
    <h1>{{ $order->order_number }}</h1>
    <div class="sub">
        Order Details &nbsp;·&nbsp; Generated {{ now()->format('d M Y, H:i') }}
        @if($order->whatsapp_order_id)
            &nbsp;·&nbsp; WA: {{ $order->whatsapp_order_id }}
        @endif
    </div>
</div>

{{-- ── Order Info ───────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">Order Information</div>
    <table class="info-grid">
        <tr>
            <td class="label">Customer</td>
            <td class="value">{{ $order->customer_name }}</td>
            <td class="label">Phone</td>
            <td class="value">{{ $order->customer_phone ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">WhatsApp Order ID</td>
            <td class="value whatsapp-id">{{ $order->whatsapp_order_id ?: '—' }}</td>
            <td class="label">Product Type</td>
            <td class="value">{{ $order->product_type_label }}</td>
        </tr>
        <tr>
            <td class="label">Quantity</td>
            <td class="value" style="font-size:14px;color:#0d6efd">{{ number_format($order->quantity) }}</td>
            <td class="label">Priority</td>
            <td class="value">
                <span class="badge badge-{{ $order->priority_badge }}">{{ ucfirst($order->priority) }}</span>
            </td>
        </tr>
        <tr>
            <td class="label">Order Date</td>
            <td class="value">{{ $order->order_date->format('d M Y') }}</td>
            <td class="label">Delivery Date</td>
            <td class="value {{ $order->was_delivered_late ? 'late' : '' }}">
                {{ $order->delivery_date->format('d M Y') }}
                @if($order->stage === 'delivered')
                    @if($order->was_delivered_late)
                        (Delivered {{ $order->days_delivered_late }} day(s) late)
                    @else
                        (Delivered on time)
                    @endif
                @elseif($order->is_late)
                    ({{ abs($order->days_remaining) }} day(s) late)
                @elseif($order->days_remaining === 0)
                    (Due today)
                @else
                    ({{ $order->days_remaining }} day(s))
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Stage</td>
            <td class="value"><span class="badge badge-secondary">{{ $order->stage_label }}</span></td>
            <td class="label">Status</td>
            <td class="value">
                <span class="badge badge-{{ $order->status_badge }}">
                    {{ ucwords(str_replace('_', ' ', $order->status)) }}
                </span>
            </td>
        </tr>
    </table>
</div>

{{-- ── Pipeline ─────────────────────────────────────────── --}}
<div class="section">
    <div class="section-title">Production Pipeline</div>
    @php
        $stageOrder = ['design' => 0, 'print' => 1, 'sew' => 2, 'ready' => 3, 'delivered' => 4];
        $currentIdx = $stageOrder[$order->stage] ?? 0;
        $steps = [
            ['key' => 'design',    'label' => 'Design'],
            ['key' => 'print',     'label' => 'Print'],
            ['key' => 'sew',       'label' => 'Sewing'],
            ['key' => 'ready',     'label' => 'Ready'],
            ['key' => 'delivered', 'label' => 'Delivered'],
        ];
    @endphp
    <div class="pipeline">
        @foreach($steps as $step)
            @php
                $idx  = $stageOrder[$step['key']];
                $done = $idx < $currentIdx;
                $now  = $idx === $currentIdx;
            @endphp
            <div class="pipeline-step">
                <div class="pipeline-dot {{ $done ? 'step-done' : ($now ? 'step-current' : 'step-pending') }}">
                    {{ $done ? '✓' : ($idx + 1) }}
                </div>
                <div class="pipeline-label {{ $now ? 'active' : '' }}">{{ $step['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- ── Notes ────────────────────────────────────────────── --}}
@if($order->notes)
<div class="section">
    <div class="section-title">Internal Notes</div>
    <p style="font-size:10.5px;line-height:1.6;color:#444">{{ $order->notes }}</p>
</div>
@endif

{{-- ── Players ──────────────────────────────────────────── --}}
@if($order->players->isNotEmpty())
<div class="section">
    <div class="section-title">Name &amp; Number List ({{ $order->players->count() }} players)</div>
    <table class="players">
        <thead>
            <tr>
                <th class="center" style="width:5%">#</th>
                <th style="width:35%">Player Name</th>
                <th class="center" style="width:15%">Jersey #</th>
                <th class="center" style="width:15%">Size</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->players as $player)
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                <td><strong>{{ $player->player_name }}</strong></td>
                <td class="center">{{ $player->jersey_number }}</td>
                <td class="center">{{ $player->size ?: '—' }}</td>
                <td>{{ $player->notes ?: '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Production Schedule ──────────────────────────────── --}}
@if($order->productionSchedules->isNotEmpty())
<div class="section">
    <div class="section-title">Production Schedule</div>
    <table class="players">
        <thead>
            <tr>
                <th>Department</th>
                <th class="center">Scheduled Date</th>
                <th class="center">Qty</th>
                <th class="center">Overtime</th>
                <th class="center">Completed</th>
            </tr>
        </thead>
        <tbody>
            @foreach(['design' => 'Design', 'print' => 'Print', 'sew' => 'Sewing'] as $dept => $label)
                @php $slot = $order->productionSchedules->firstWhere('department', $dept); @endphp
                <tr>
                    <td>{{ $label }}</td>
                    <td class="center">{{ $slot ? $slot->scheduled_date->format('d M Y') : '—' }}</td>
                    <td class="center">{{ $slot ? number_format($slot->quantity_scheduled) : '—' }}</td>
                    <td class="center">{{ ($slot && $slot->is_overtime) ? 'Yes' : 'No' }}</td>
                    <td class="center">
                        @if($slot && $slot->completed_at)
                            ✓ {{ $slot->completed_at->format('d M Y') }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Footer ───────────────────────────────────────────── --}}
<div class="footer">
    <div class="footer-left">ERP Classico &nbsp;·&nbsp; {{ config('app.name') }}</div>
    <div class="footer-right">Printed {{ now()->format('d M Y H:i') }}</div>
</div>

</body>
</html>
