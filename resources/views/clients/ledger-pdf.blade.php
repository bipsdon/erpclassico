<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $client->name }} — Ledger</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }

        .page-header { background: #1e3a5f; color: #fff; padding: 16px 24px 12px; margin-bottom: 14px; }
        .page-header h1 { font-size: 16px; font-weight: 700; letter-spacing: .4px; }
        .page-header .sub { font-size: 9.5px; opacity: .75; margin-top: 2px; }

        .summary { margin: 0 24px 14px; display: table; width: calc(100% - 48px); border-collapse: collapse; }
        .summary-box   { display: table-cell; text-align: center; padding: 5px 4px; }
        .summary-inner { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 3px; padding: 7px 4px; }
        .summary-num   { font-size: 12px; font-weight: 700; }
        .summary-lbl   { font-size: 8px; color: #666; margin-top: 1px; }
        .c-blue   { color: #1e3a5f; }
        .c-green  { color: #198754; }
        .c-red    { color: #dc3545; }
        .c-grey   { color: #6c757d; }

        .section-title {
            font-size: 8.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .7px; color: #1e3a5f;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 3px; margin: 0 24px 7px;
        }

        table.ledger { width: calc(100% - 48px); margin: 0 24px; border-collapse: collapse; }
        table.ledger th {
            background: #1e3a5f; color: #fff;
            padding: 5px 7px; font-size: 8px;
            text-transform: uppercase; letter-spacing: .3px;
            text-align: left;
        }
        table.ledger th.r, table.ledger td.r { text-align: right; }
        table.ledger td { padding: 5px 7px; border-bottom: 1px solid #e9ecef; font-size: 9px; vertical-align: top; }
        table.ledger tr.row-order:nth-child(even) td  { background: #fafafa; }
        table.ledger tr.row-payment td { background: #f0fdf4; font-style: italic; color: #166534; }
        table.ledger tr.row-payment td.r { font-style: normal; font-weight: 600; }
        table.ledger tr.totals td {
            background: #eef2ff; font-weight: 700;
            border-top: 2px solid #1e3a5f; font-size: 9.5px;
            font-style: normal;
        }
        .stage-badge {
            display: inline-block; padding: 1px 5px; border-radius: 2px;
            font-size: 7.5px; font-weight: 700; text-transform: uppercase;
        }
        .s-delivered { background: #198754; color: #fff; }
        .s-ready     { background: #0dcaf0; color: #000; }
        .s-design    { background: #0d6efd; color: #fff; }
        .s-print     { background: #ffc107; color: #000; }
        .s-sew       { background: #6c757d; color: #fff; }
        .bal-due    { color: #dc3545; font-weight: 700; }
        .bal-credit { color: #198754; font-weight: 700; }
        .bal-zero   { color: #6c757d; }
        .footer {
            margin: 14px 24px 0; border-top: 1px solid #dee2e6;
            padding-top: 7px; font-size: 8px; color: #aaa; text-align: right;
        }
    </style>
</head>
<body>

<div class="page-header">
    <h1>{{ $client->name }} — Account Statement</h1>
    <div class="sub">
        Generated {{ now()->format('d M Y, H:i') }}
        @if($client->phone) &nbsp;·&nbsp; {{ $client->phone }} @endif
    </div>
</div>

{{-- ── Summary ──────────────────────────────────────────── --}}
<div class="summary">
    <div class="summary-box">
        <div class="summary-inner">
            <div class="summary-num c-blue">Rs. {{ number_format($totalBilled, 2) }}</div>
            <div class="summary-lbl">Total Billed</div>
        </div>
    </div>
    <div class="summary-box">
        <div class="summary-inner">
            <div class="summary-num c-green">Rs. {{ number_format($totalPaid, 2) }}</div>
            <div class="summary-lbl">Total Paid</div>
        </div>
    </div>
    <div class="summary-box">
        <div class="summary-inner">
            @if($netBalance < 0)
                <div class="summary-num c-green">(Rs. {{ number_format(abs($netBalance), 2) }})</div>
                <div class="summary-lbl">In Credit</div>
            @elseif($netBalance == 0)
                <div class="summary-num c-grey">Rs. 0.00</div>
                <div class="summary-lbl">Settled</div>
            @else
                <div class="summary-num c-red">Rs. {{ number_format($netBalance, 2) }}</div>
                <div class="summary-lbl">Outstanding</div>
            @endif
        </div>
    </div>
</div>

{{-- ── Statement table ─────────────────────────────────── --}}
<div class="section-title">Account Statement</div>

<table class="ledger">
    <thead>
        <tr>
            <th style="width:9%">Date</th>
            <th>Description</th>
            <th class="r" style="width:13%">Debit (Rs.)</th>
            <th class="r" style="width:13%">Credit (Rs.)</th>
            <th class="r" style="width:14%">Balance (Rs.)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($events as $event)
            @if($event['type'] === 'order')
                @php
                    $order  = $event['order'];
                    $debit  = $event['debit'];
                    $runBal = $event['running_balance'];
                    $stageClass = 's-' . $order->stage;
                @endphp
                <tr class="row-order">
                    <td>{{ $order->order_date->format('d M Y') }}</td>
                    <td>
                        {{ $order->order_number }}
                        @if($order->whatsapp_order_id)
                            &nbsp;· {{ $order->whatsapp_order_id }}
                        @endif
                        &nbsp;<span class="stage-badge {{ $stageClass }}">{{ $order->stage_label }}</span>
                    </td>
                    <td class="r">{{ $debit > 0 ? number_format($debit, 2) : '—' }}</td>
                    <td class="r">—</td>
                    <td class="r {{ $runBal < 0 ? 'bal-credit' : ($runBal > 0 ? 'bal-due' : 'bal-zero') }}">
                        @if($runBal < 0)({{ number_format(abs($runBal), 2) }})
                        @else {{ number_format($runBal, 2) }}
                        @endif
                    </td>
                </tr>
            @else
                @php
                    $pmt    = $event['payment'];
                    $runBal = $event['running_balance'];
                @endphp
                <tr class="row-payment">
                    <td>{{ $pmt->payment_date->format('d M Y') }}</td>
                    <td>
                        Payment received
                        @if($pmt->method_label !== '—') ({{ $pmt->method_label }})@endif
                        @if($pmt->reference) · Ref: {{ $pmt->reference }}@endif
                        @if($pmt->notes)<br><span style="font-size:8px">{{ $pmt->notes }}</span>@endif
                    </td>
                    <td class="r">—</td>
                    <td class="r">{{ number_format($pmt->amount, 2) }}</td>
                    <td class="r {{ $runBal < 0 ? 'bal-credit' : ($runBal > 0 ? 'bal-due' : 'bal-zero') }}">
                        @if($runBal < 0)({{ number_format(abs($runBal), 2) }})
                        @else {{ number_format($runBal, 2) }}
                        @endif
                    </td>
                </tr>
            @endif
        @endforeach
    </tbody>
    <tfoot>
        <tr class="totals">
            <td colspan="2" style="text-align:right">Totals</td>
            <td class="r">{{ number_format($totalBilled, 2) }}</td>
            <td class="r c-green">{{ number_format($totalPaid, 2) }}</td>
            <td class="r {{ $netBalance < 0 ? 'bal-credit' : ($netBalance > 0 ? 'bal-due' : 'bal-zero') }}">
                @if($netBalance < 0)({{ number_format(abs($netBalance), 2) }}) CR
                @else {{ number_format($netBalance, 2) }}
                @endif
            </td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    {{ config('app.name') }} &nbsp;·&nbsp; Printed {{ now()->format('d M Y H:i') }}
    &nbsp;·&nbsp; Negative balance (amount) = client is in credit
</div>

</body>
</html>
