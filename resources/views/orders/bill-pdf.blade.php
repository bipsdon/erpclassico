<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill — {{ $order->order_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #fff;
        }

        /* ── Page header / letterhead ──────────────────────── */
        .letterhead {
            border-bottom: 3px solid #1e3a5f;
            padding: 20px 30px 16px;
            display: table;
            width: 100%;
        }
        .letterhead-left  { display: table-cell; vertical-align: middle; width: 60%; }
        .letterhead-right { display: table-cell; vertical-align: middle; text-align: right; width: 40%; }

        /* Logo block — used when no image file is present */
        .logo-text {
            font-size: 22px;
            font-weight: 700;
            color: #1e3a5f;
            letter-spacing: 1px;
            line-height: 1.1;
        }
        .logo-text span {
            color: #e63946;
        }
        .logo-tagline {
            font-size: 9px;
            color: #666;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 3px;
        }
        /* Actual logo image — shown when file exists */
        .logo-img { height: 52px; width: auto; }

        .company-address {
            font-size: 9.5px;
            color: #555;
            line-height: 1.6;
            text-align: right;
        }
        .company-address strong {
            font-size: 10.5px;
            color: #1a1a1a;
            display: block;
            margin-bottom: 2px;
        }

        /* ── Bill title banner ──────────────────────────────── */
        .bill-title-bar {
            background: #1e3a5f;
            color: #fff;
            padding: 10px 30px;
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .bill-title-bar .left  { display: table-cell; vertical-align: middle; }
        .bill-title-bar .right { display: table-cell; vertical-align: middle; text-align: right; }
        .bill-title-bar h1 { font-size: 16px; font-weight: 700; letter-spacing: .5px; }
        .bill-title-bar .bill-num { font-size: 11px; opacity: .8; margin-top: 2px; }
        .bill-title-bar .bill-date { font-size: 10px; opacity: .75; }

        /* ── Two-column info block ──────────────────────────── */
        .info-row {
            display: table;
            width: calc(100% - 60px);
            margin: 0 30px 20px;
            border-collapse: collapse;
        }
        .info-col { display: table-cell; width: 50%; vertical-align: top; }
        .info-col:last-child { padding-left: 20px; }

        .info-box {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px 12px;
        }
        .info-box-title {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #1e3a5f;
            margin-bottom: 6px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 4px;
        }
        .info-line { margin-bottom: 3px; font-size: 10.5px; line-height: 1.5; }
        .info-line .lbl { color: #666; font-size: 9.5px; }
        .info-line .val { font-weight: 600; }
        .wa-id { color: #25D366; font-weight: 700; }

        /* ── Bill line items table ──────────────────────────── */
        .section-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #1e3a5f;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 3px;
            margin: 0 30px 8px;
        }

        table.items {
            width: calc(100% - 60px);
            margin: 0 30px 20px;
            border-collapse: collapse;
        }
        table.items th {
            background: #1e3a5f;
            color: #fff;
            padding: 7px 10px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .5px;
            text-align: left;
        }
        table.items th.r, table.items td.r { text-align: right; }
        table.items td {
            padding: 7px 10px;
            border-bottom: 1px solid #e9ecef;
            font-size: 10.5px;
            vertical-align: top;
        }
        table.items tr:nth-child(even) td { background: #f8f9fa; }

        /* ── Summary / totals block ─────────────────────────── */
        .totals-wrap {
            width: calc(100% - 60px);
            margin: 0 30px 20px;
            display: table;
        }
        .totals-spacer { display: table-cell; width: 50%; }
        .totals-box    { display: table-cell; width: 50%; }

        table.totals { width: 100%; border-collapse: collapse; border: 1px solid #dee2e6; border-radius: 4px; }
        table.totals td { padding: 6px 12px; font-size: 10.5px; border-bottom: 1px solid #e9ecef; }
        table.totals td.lbl { color: #555; }
        table.totals td.val { text-align: right; font-weight: 600; }
        table.totals tr.grand td {
            background: #1e3a5f;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            border-bottom: none;
        }
        table.totals tr.grand td.val { color: #fff; }
        .balance-paid { color: #198754; }
        .balance-due  { color: #dc3545; }

        /* ── Payment status badge ───────────────────────────── */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .status-paid    { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .status-partial { background: #fef9c3; color: #854d0e; border: 1px solid #fde68a; }
        .status-unpaid  { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        /* ── Notes ──────────────────────────────────────────── */
        .bill-notes {
            margin: 0 30px 16px;
            padding: 8px 12px;
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
            font-size: 10px;
            color: #444;
            line-height: 1.6;
        }

        /* ── Footer / disclaimer ────────────────────────────── */
        .bill-footer {
            margin: 10px 30px 0;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        .disclaimer {
            font-size: 9px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 6px;
        }
        .disclaimer strong { color: #1a1a1a; }
        .generated-line {
            font-size: 8.5px;
            color: #aaa;
            text-align: right;
        }
    </style>
</head>
<body>

{{-- ── Letterhead ──────────────────────────────────────── --}}
<div class="letterhead">
    <div class="letterhead-left">
        @if($logoPath && file_exists($logoPath))
            <img src="{{ $logoPath }}" class="logo-img" alt="Classico Logo">
        @else
            <div class="logo-text">CLASSICO <span>SPORTS</span></div>
            <div class="logo-tagline">Studio &nbsp;·&nbsp; Pvt. Ltd.</div>
        @endif
    </div>
    <div class="letterhead-right">
        <div class="company-address">
            <strong>Classico Sports Studio Pvt. Ltd.</strong>
            Satungal Complex, Satungal<br>
            Kathmandu, Nepal
        </div>
    </div>
</div>

{{-- ── Bill title bar ──────────────────────────────────── --}}
<div class="bill-title-bar">
    <div class="left">
        <h1>BILL / INVOICE</h1>
        <div class="bill-num">{{ $order->order_number }}</div>
    </div>
    <div class="right">
        <div class="bill-date">Date: {{ now()->format('d M Y') }}</div>
        @if($order->whatsapp_order_id)
            <div class="bill-date" style="margin-top:3px">WhatsApp ID: {{ $order->whatsapp_order_id }}</div>
        @endif
        @php
            $balance = $bill ? ($bill->amount - $bill->received) : null;
            if (!$bill || $bill->amount == 0) {
                $statusClass = 'status-unpaid';
                $statusLabel = 'Unbilled';
            } elseif ($balance <= 0) {
                $statusClass = 'status-paid';
                $statusLabel = 'Paid';
            } elseif ($bill->received > 0) {
                $statusClass = 'status-partial';
                $statusLabel = 'Partial';
            } else {
                $statusClass = 'status-unpaid';
                $statusLabel = 'Unpaid';
            }
        @endphp
        <div style="margin-top:5px">
            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>
    </div>
</div>

{{-- ── Bill To / Order Info ─────────────────────────────── --}}
<div class="info-row">
    <div class="info-col">
        <div class="info-box">
            <div class="info-box-title">Bill To</div>
            <div class="info-line">
                <div class="val" style="font-size:12px">{{ $order->customer_name }}</div>
            </div>
            @if($order->customer_phone)
                <div class="info-line">
                    <span class="lbl">Phone: </span>
                    <span class="val">{{ $order->customer_phone }}</span>
                </div>
            @endif
            @if($order->whatsapp_order_id)
                <div class="info-line">
                    <span class="lbl">WhatsApp ID: </span>
                    <span class="val wa-id">{{ $order->whatsapp_order_id }}</span>
                </div>
            @endif
        </div>
    </div>
    <div class="info-col">
        <div class="info-box">
            <div class="info-box-title">Order Details</div>
            <div class="info-line">
                <span class="lbl">Order No.: </span>
                <span class="val">{{ $order->order_number }}</span>
            </div>
            <div class="info-line">
                <span class="lbl">Product: </span>
                <span class="val">{{ $order->product_type_label }}</span>
            </div>
            <div class="info-line">
                <span class="lbl">Quantity: </span>
                <span class="val">{{ number_format($order->quantity) }} pcs</span>
            </div>
            <div class="info-line">
                <span class="lbl">Order Date: </span>
                <span class="val">{{ $order->order_date->format('d M Y') }}</span>
            </div>
            <div class="info-line">
                <span class="lbl">Delivery Date: </span>
                <span class="val">{{ $order->delivery_date->format('d M Y') }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Line items ───────────────────────────────────────── --}}
<div class="section-title">Particulars</div>
<table class="items">
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th>Description</th>
            <th class="r" style="width:12%">Qty</th>
            <th class="r" style="width:22%">Amount (Rs.)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td>
                {{ $order->product_type_label }}
                @if($order->whatsapp_order_id)
                    — Ref: {{ $order->whatsapp_order_id }}
                @endif
                @if($bill && $bill->notes)
                    <div style="color:#666;font-size:9.5px;margin-top:2px">{{ $bill->notes }}</div>
                @endif
            </td>
            <td class="r">{{ number_format($order->quantity) }}</td>
            <td class="r">
                @if($bill && $bill->amount > 0)
                    Rs. {{ number_format($bill->amount, 2) }}
                @else
                    <span style="color:#aaa">—</span>
                @endif
            </td>
        </tr>
    </tbody>
</table>

{{-- ── Totals ───────────────────────────────────────────── --}}
<div class="totals-wrap">
    <div class="totals-spacer"></div>
    <div class="totals-box">
        <table class="totals">
            <tr>
                <td class="lbl">Total Amount</td>
                <td class="val">
                    @if($bill && $bill->amount > 0)
                        Rs. {{ number_format($bill->amount, 2) }}
                    @else
                        <span style="color:#aaa">Not set</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="lbl">Amount Received</td>
                <td class="val" style="color:#198754">
                    @if($bill && $bill->received > 0)
                        Rs. {{ number_format($bill->received, 2) }}
                    @else
                        Rs. 0.00
                    @endif
                </td>
            </tr>
            <tr class="grand">
                <td class="lbl">Balance Due</td>
                <td class="val">
                    @if($bill && $bill->amount > 0)
                        Rs. {{ number_format(max(0, $bill->amount - $bill->received), 2) }}
                    @else
                        <span style="font-size:10px;opacity:.7">Pending billing</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>

{{-- ── Footer / disclaimer ─────────────────────────────── --}}
<div class="bill-footer">
    <div class="disclaimer">
        <strong>Note:</strong> This is a computer-generated bill and does not require a physical signature or stamp.
        For an official physical copy, please contact <strong>Classico Sports Studio Pvt. Ltd.</strong>
    </div>
    <div class="generated-line">
        Generated by {{ config('app.name') }} &nbsp;·&nbsp; {{ now()->format('d M Y, H:i') }}
    </div>
</div>

</body>
</html>
