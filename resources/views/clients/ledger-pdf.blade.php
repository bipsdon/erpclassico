<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $client->name }} — Ledger</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }

        .page-header { background: #1e3a5f; color: #fff; padding: 18px 24px 14px; margin-bottom: 18px; }
        .page-header h1 { font-size: 17px; font-weight: 700; letter-spacing: .4px; }
        .page-header .sub { font-size: 10px; opacity: .75; margin-top: 3px; }

        .meta { margin: 0 24px 14px; display: table; width: calc(100% - 48px); border-collapse: collapse; }
        .meta td { padding: 4px 8px; vertical-align: top; width: 33.3%; }
        .meta .lbl { font-size: 8.5px; text-transform: uppercase; letter-spacing: .5px; color: #666; }
        .meta .val { font-weight: 600; font-size: 10.5px; }

        .summary { margin: 0 24px 16px; display: table; width: calc(100% - 48px); border-collapse: collapse; }
        .summary-box { display: table-cell; text-align: center; padding: 8px 4px; }
        .summary-inner { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 8px; }
        .summary-num { font-size: 14px; font-weight: 700; }
        .summary-lbl { font-size: 8.5px; color: #666; margin-top: 2px; }
        .color-blue   { color: #1e3a5f; }
        .color-green  { color: #198754; }
        .color-red    { color: #dc3545; }

        .section-title {
            font-size: 9px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .7px; color: #1e3a5f;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 3px; margin: 0 24px 8px;
        }

        table.ledger { width: calc(100% - 48px); margin: 0 24px; border-collapse: collapse; }
        table.ledger th {
            background: #1e3a5f; color: #fff;
            padding: 6px 7px; font-size: 8.5px;
            text-transform: uppercase; letter-spacing: .4px;
            text-align: left;
        }
        table.ledger th.r, table.ledger td.r { text-align: right; }
        table.ledger td { padding: 5px 7px; border-bottom: 1px solid #e9ecef; font-size: 9.5px; }
        table.ledger tr:nth-child(even) td { background: #f8f9fa; }
        table.ledger tr.totals td {
            background: #eef2ff; font-weight: 700; border-top: 2px solid #1e3a5f;
            font-size: 10px;
        }
        .stage-badge {
            display: inline-block; padding: 1px 6px; border-radius: 2px;
            font-size: 8px; font-weight: 700; text-transform: uppercase;
        }
        .stage-delivered { background: #198754; color: #fff; }
        .stage-ready     { background: #0dcaf0; color: #000; }
        .stage-design    { background: #0d6efd; color: #fff; }
        .stage-print     { background: #ffc107; color: #000; }
        .stage-sew       { background: #6c757d; color: #fff; }
        .balance-due  { color: #dc3545; font-weight: 700; }
        .balance-paid { color: #198754; font-weight: 700; }
        .footer { margin: 18px 24px 0; border-top: 1px solid #dee2e6; padding-top: 8px;
                  font-size: 8.5px; color: #999; text-align: right; }
    </style>
</head>
<body>

{{-- ── Header ─────────────────────────────────────────── --}}
<div class="page-header">
    <h1>{{ $client->name }}</h1>
    <div class="sub">
        Client Ledger &nbsp;·&nbsp; Generated {{ now()->format('d M Y, H:i') }}
        @if($client->phone) &nbsp;·&nbsp; {{ $client->phone }} @endif
    </div>
</div>

{{-- ── Summary boxes ───────────────────────────────────── --}}
<div class="summary">
    <div class="summary-box">
        <div class="summary-inner">
            <div class="summary-num color-blue">{{ $orders->count() }}</div>
            <div class="summary-lbl">Total Orders</div>
        </div>
    </div>
    <div class="summary-box">
        <div class="summary-inner">
            <div class="summary-num color-blue">৳ {{ number_format($totalBilled, 2) }}</div>
            <div class="summary-lbl">Total Billed</div>
        </div>
    </div>
    <div class="summary-box">
        <div class="summary-inner">
            <div class="summary-num color-green">৳ {{ number_format($totalReceived, 2) }}</div>
            <div class="summary-lbl">Total Received</div>
        </div>
    </div>
    <div class="summary-box">
        <div class="summary-inner">
            <div class="summary-num {{ $totalBalance > 0 ? 'color-red' : 'color-green' }}">
                ৳ {{ number_format($totalBalance, 2) }}
            </div>
            <div class="summary-lbl">Outstanding Balance</div>
        </div>
    </div>
</div>

{{-- ── Table ────────────────────────────────────────────── --}}
<div class="section-title" style="margin-top:4px">Order Ledger</div>

<table class="ledger">
    <thead>
        <tr>
            <th>#</th>
            <th>Order No.</th>
            <th>WhatsApp ID</th>
            <th>Order Date</th>
            <th>Delivered</th>
            <th>Stage</th>
            <th class="r">Amount (৳)</th>
            <th class="r">Received (৳)</th>
            <th class="r">Balance (৳)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($orders as $i => $order)
            @php
                $bill          = $order->bill;
                $amount        = $bill?->amount   ?? 0;
                $received      = $bill?->received ?? 0;
                $balance       = $amount - $received;
                $deliveredLog  = $order->stageLogs->first(fn($l) => $l->to_stage === 'delivered');
                $deliveredDate = $deliveredLog?->created_at?->format('d M Y') ?? '—';
                $stageClass    = 'stage-' . $order->stage;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->whatsapp_order_id ?? '—' }}</td>
                <td>{{ $order->order_date->format('d M Y') }}</td>
                <td>{{ $deliveredDate }}</td>
                <td><span class="stage-badge {{ $stageClass }}">{{ $order->stage_label }}</span></td>
                <td class="r">{{ $amount > 0 ? number_format($amount, 2) : '—' }}</td>
                <td class="r">{{ $received > 0 ? number_format($received, 2) : '—' }}</td>
                <td class="r {{ $amount > 0 ? ($balance > 0 ? 'balance-due' : 'balance-paid') : '' }}">
                    {{ $amount > 0 ? number_format($balance, 2) : '—' }}
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="totals">
            <td colspan="6" style="text-align:right">Total</td>
            <td class="r">{{ number_format($totalBilled, 2) }}</td>
            <td class="r">{{ number_format($totalReceived, 2) }}</td>
            <td class="r {{ $totalBalance > 0 ? 'balance-due' : 'balance-paid' }}">
                {{ number_format($totalBalance, 2) }}
            </td>
        </tr>
    </tfoot>
</table>

<div class="footer">{{ config('app.name') }} &nbsp;·&nbsp; Printed {{ now()->format('d M Y H:i') }}</div>

</body>
</html>
