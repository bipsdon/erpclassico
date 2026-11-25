<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill{{ $billNumber ? ' — '.$billNumber : '' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            background: #fff;
        }

        /* ── Letterhead ─────────────────────────────────────── */
        .letterhead {
            border-bottom: 3px solid #1e3a5f;
            padding: 20px 30px 16px;
            display: table;
            width: 100%;
        }
        .lh-left  { display: table-cell; vertical-align: middle; width: 55%; }
        .lh-right { display: table-cell; vertical-align: middle; text-align: right; width: 45%; }

        .logo-text       { font-size: 22px; font-weight: 700; color: #1e3a5f; letter-spacing: 1px; line-height: 1.1; }
        .logo-text span  { color: #e63946; }
        .logo-tagline    { font-size: 9px; color: #666; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 2px; }
        .logo-img        { height: 52px; width: auto; }

        .company-block        { font-size: 9.5px; color: #555; line-height: 1.65; text-align: right; }
        .company-block strong { font-size: 11px; color: #1a1a1a; display: block; margin-bottom: 1px; }
        .company-vat          { font-size: 9px; color: #888; margin-top: 1px; }

        /* ── Title bar ──────────────────────────────────────── */
        .title-bar {
            background: #1e3a5f;
            color: #fff;
            padding: 10px 30px;
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .title-bar .tl { display: table-cell; vertical-align: middle; }
        .title-bar .tr { display: table-cell; vertical-align: middle; text-align: right; }
        .title-bar h1  { font-size: 17px; font-weight: 700; letter-spacing: .5px; }
        .title-bar .sub { font-size: 10px; opacity: .8; margin-top: 2px; }

        /* ── Bill-to / meta ─────────────────────────────────── */
        .meta-row {
            display: table;
            width: calc(100% - 60px);
            margin: 0 30px 20px;
            border-collapse: collapse;
        }
        .meta-col { display: table-cell; width: 50%; vertical-align: top; }
        .meta-col:last-child { padding-left: 20px; }
        .meta-box {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px 12px;
        }
        .meta-box-title {
            font-size: 8.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .8px; color: #1e3a5f;
            margin-bottom: 6px; border-bottom: 1px solid #e9ecef; padding-bottom: 4px;
        }
        .meta-line        { margin-bottom: 3px; font-size: 10.5px; line-height: 1.5; }
        .meta-line .lbl   { color: #666; font-size: 9.5px; }
        .meta-name        { font-size: 13px; font-weight: 700; color: #1a1a1a; }

        /* ── Items table ────────────────────────────────────── */
        .section-title {
            font-size: 9px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .8px; color: #1e3a5f;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 3px; margin: 0 30px 8px;
        }
        table.items {
            width: calc(100% - 60px);
            margin: 0 30px 0;
            border-collapse: collapse;
        }
        table.items th {
            background: #1e3a5f; color: #fff;
            padding: 7px 10px; font-size: 9px;
            text-transform: uppercase; letter-spacing: .4px; text-align: left;
        }
        table.items th.r, table.items td.r { text-align: right; }
        table.items th.c, table.items td.c { text-align: center; }
        table.items td {
            padding: 7px 10px;
            border-bottom: 1px solid #e9ecef;
            font-size: 10.5px; vertical-align: top;
        }
        table.items tr:nth-child(even) td { background: #f8f9fa; }
        table.items .desc { font-size: 9.5px; color: #666; margin-top: 2px; }

        /* ── Totals ─────────────────────────────────────────── */
        .totals-wrap {
            display: table; width: calc(100% - 60px);
            margin: 0 30px 20px; border-collapse: collapse;
        }
        .totals-gap { display: table-cell; width: 55%; }
        .totals-box { display: table-cell; width: 45%; }
        table.totals { width: 100%; border-collapse: collapse; border: 1px solid #dee2e6; }
        table.totals td { padding: 6px 12px; font-size: 10.5px; border-bottom: 1px solid #e9ecef; }
        table.totals td.lbl { color: #555; }
        table.totals td.val { text-align: right; font-weight: 600; }
        table.totals tr.grand td {
            background: #1e3a5f; color: #fff;
            font-size: 12px; font-weight: 700;
            border-bottom: none;
        }
        table.totals tr.grand td.val { color: #fff; }

        /* ── Notes ──────────────────────────────────────────── */
        .notes-wrap {
            margin: 0 30px 16px;
            padding: 8px 12px;
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
            font-size: 10px; color: #444; line-height: 1.6;
        }

        /* ── Footer ─────────────────────────────────────────── */
        .bill-footer {
            margin: 10px 30px 0;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        .disclaimer {
            font-size: 9px; color: #555; line-height: 1.6; margin-bottom: 5px;
        }
        .disclaimer strong { color: #1a1a1a; }
        .generated { font-size: 8.5px; color: #aaa; text-align: right; }
    </style>
</head>
<body>

{{-- ── Letterhead ──────────────────────────────────────── --}}
<div class="letterhead">
    <div class="lh-left">
        @if($logoPath && file_exists($logoPath))
            <img src="{{ $logoPath }}" class="logo-img" alt="Classico Logo">
        @else
            <div class="logo-text">CLASSICO <span>SPORTS</span></div>
            <div class="logo-tagline">Studio &nbsp;·&nbsp; Pvt. Ltd.</div>
        @endif
    </div>
    <div class="lh-right">
        <div class="company-block">
            <strong>Classico Sports Studio Pvt. Ltd.</strong>
            Satungal Complex, Satungal<br>
            Kathmandu, Nepal<br>
            +977 981-0495547
            <div class="company-vat">VAT No: 622372855</div>
        </div>
    </div>
</div>

{{-- ── Title bar ────────────────────────────────────────── --}}
<div class="title-bar">
    <div class="tl">
        <h1>BILL / INVOICE</h1>
        @if($billNumber)
            <div class="sub">{{ $billNumber }}</div>
        @endif
    </div>
    <div class="tr">
        <div class="sub">Date: {{ \Carbon\Carbon::parse($billDate)->format('d M Y') }}</div>
    </div>
</div>

{{-- ── Bill To ──────────────────────────────────────────── --}}
<div class="meta-row">
    <div class="meta-col">
        <div class="meta-box">
            <div class="meta-box-title">Bill To</div>
            <div class="meta-name">{{ $billTo }}</div>
        </div>
    </div>
    <div class="meta-col">
        <div class="meta-box">
            <div class="meta-box-title">Invoice Info</div>
            @if($billNumber)
                <div class="meta-line">
                    <span class="lbl">Invoice No.: </span>
                    <span style="font-weight:600">{{ $billNumber }}</span>
                </div>
            @endif
            <div class="meta-line">
                <span class="lbl">Date: </span>
                <span style="font-weight:600">{{ \Carbon\Carbon::parse($billDate)->format('d M Y') }}</span>
            </div>
            <div class="meta-line">
                <span class="lbl">Items: </span>
                <span style="font-weight:600">{{ $items->count() }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Items table ──────────────────────────────────────── --}}
<div class="section-title">Particulars</div>
<table class="items">
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th>Product / Service</th>
            <th class="c" style="width:10%">Qty</th>
            <th class="r" style="width:18%">Unit Price (Rs.)</th>
            <th class="r" style="width:18%">Total (Rs.)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>
                {{ $item['title'] }}
                @if($item['description'])
                    <div class="desc">{{ $item['description'] }}</div>
                @endif
            </td>
            <td class="c">{{ rtrim(rtrim(number_format($item['qty'], 2), '0'), '.') }}</td>
            <td class="r">{{ number_format($item['price'], 2) }}</td>
            <td class="r" style="font-weight:600">{{ number_format($item['total'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ── Totals ───────────────────────────────────────────── --}}
<div class="totals-wrap" style="margin-top:0;padding-top:0">
    <div class="totals-gap"></div>
    <div class="totals-box">
        <table class="totals">
            <tr>
                <td class="lbl">Subtotal</td>
                <td class="val">Rs. {{ number_format($subtotal, 2) }}</td>
            </tr>
            <tr class="grand">
                <td class="lbl">Grand Total</td>
                <td class="val">Rs. {{ number_format($subtotal, 2) }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- ── Notes ───────────────────────────────────────────── --}}
@if($notes)
<div class="notes-wrap" style="margin-top:16px">
    <strong>Notes:</strong> {{ $notes }}
</div>
@endif

{{-- ── Footer ──────────────────────────────────────────── --}}
<div class="bill-footer">
    <div class="disclaimer">
        <strong>Note:</strong> This is a computer-generated bill and does not require a physical signature or stamp.
        For an official physical copy, please contact <strong>Classico Sports Studio Pvt. Ltd.</strong>
    </div>
    <div class="generated">
        Generated by {{ config('app.name') }} &nbsp;·&nbsp; {{ now()->format('d M Y, H:i') }}
    </div>
</div>

</body>
</html>
