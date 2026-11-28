<?php

namespace App\Exports;

use App\Models\Client;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientLedgerExport implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithStyles,
    WithColumnWidths,
    WithProperties,
    WithEvents
{
    private Collection $events;
    private float $totalBilled;
    private float $totalPaid;
    private float $netBalance;
    private int   $dataRowCount;

    public function __construct(private readonly Client $client)
    {
        $orders   = $client->orders()->with(['bill', 'stageLogs'])->orderBy('order_date')->orderBy('id')->get();
        $payments = $client->payments()->orderBy('payment_date')->orderBy('id')->get();

        $this->totalBilled = $orders->sum(fn ($o) => optional($o->bill)->amount ?? 0);
        $this->totalPaid   = $payments->sum('amount');
        $this->netBalance  = $this->totalBilled - $this->totalPaid;

        // Build the same chronological event stream
        $raw = collect();
        foreach ($orders as $order) {
            $raw->push([
                'type'     => 'order',
                'sort_key' => $order->order_date->format('Y-m-d') . '-A-' . $order->id,
                'order'    => $order,
                'debit'    => optional($order->bill)->amount ?? 0,
                'credit'   => 0,
            ]);
        }
        foreach ($payments as $payment) {
            $raw->push([
                'type'     => 'payment',
                'sort_key' => $payment->payment_date->format('Y-m-d') . '-B-' . $payment->id,
                'payment'  => $payment,
                'debit'    => 0,
                'credit'   => $payment->amount,
            ]);
        }

        $sorted  = $raw->sortBy('sort_key')->values();
        $running = 0;
        $this->events = $sorted->map(function ($event) use (&$running) {
            $running += $event['debit'] - $event['credit'];
            $event['running_balance'] = $running;
            return $event;
        });

        $this->dataRowCount = $this->events->count();
    }

    public function collection(): Collection
    {
        $rows = $this->events->map(function ($event) {
            if ($event['type'] === 'order') {
                $order  = $event['order'];
                $runBal = $event['running_balance'];
                $deliveredLog = $order->stageLogs->first(fn ($l) => $l->to_stage === 'delivered');
                return [
                    'date'        => $order->order_date->format('d M Y'),
                    'type'        => 'Order',
                    'description' => $order->order_number
                                     . ($order->whatsapp_order_id ? ' · ' . $order->whatsapp_order_id : ''),
                    'stage'       => $order->stage_label,
                    'delivered'   => $deliveredLog?->created_at?->format('d M Y') ?? '',
                    'debit'       => $event['debit'] > 0 ? $event['debit'] : '',
                    'credit'      => '',
                    'balance'     => $runBal,
                ];
            } else {
                $pmt    = $event['payment'];
                $runBal = $event['running_balance'];
                $desc   = 'Payment received';
                if ($pmt->method_label !== '—') $desc .= ' (' . $pmt->method_label . ')';
                if ($pmt->reference)             $desc .= ' · Ref: ' . $pmt->reference;
                if ($pmt->notes)                 $desc .= ' — ' . $pmt->notes;
                return [
                    'date'        => $pmt->payment_date->format('d M Y'),
                    'type'        => 'Payment',
                    'description' => $desc,
                    'stage'       => '',
                    'delivered'   => '',
                    'debit'       => '',
                    'credit'      => $pmt->amount,
                    'balance'     => $runBal,
                ];
            }
        });

        // Totals row
        $rows->push([
            'date'        => '',
            'type'        => '',
            'description' => 'TOTAL',
            'stage'       => '',
            'delivered'   => '',
            'debit'       => $this->totalBilled,
            'credit'      => $this->totalPaid,
            'balance'     => $this->netBalance,
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return ['Date', 'Type', 'Description', 'Stage', 'Delivered', 'Debit (Rs.)', 'Credit (Rs.)', 'Balance (Rs.)'];
    }

    public function title(): string
    {
        return 'Statement';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->dataRowCount + 2 + 2; // 2 header rows inserted by AfterSheet + 1 heading row + data

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $totalsStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEEF2FF']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ];
        $numberStyle = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]];

        return [
            3        => $headerStyle,  // heading row is row 3 after AfterSheet inserts 2 rows
            $lastRow => $totalsStyle,
            'F'      => $numberStyle,
            'G'      => $numberStyle,
            'H'      => $numberStyle,
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 10, 'C' => 40, 'D' => 14, 'E' => 14, 'F' => 14, 'G' => 14, 'H' => 16];
    }

    public function properties(): array
    {
        return [
            'title'   => $this->client->name . ' — Account Statement',
            'subject' => 'Client Ledger — ' . now()->format('d M Y'),
            'creator' => config('app.name'),
        ];
    }

    public function registerEvents(): array
    {
        $client     = $this->client;
        $netBalance = $this->netBalance;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($client, $netBalance) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 2);

                $sheet->setCellValue('A1', $client->name . ' — Account Statement');
                $balLabel = $netBalance < 0
                    ? 'In Credit: Rs. ' . number_format(abs($netBalance), 2)
                    : ($netBalance == 0 ? 'Fully Settled' : 'Outstanding: Rs. ' . number_format($netBalance, 2));
                $sheet->setCellValue('A2',
                    'Generated: ' . now()->format('d M Y H:i')
                    . ($client->phone ? '   |   Phone: ' . $client->phone : '')
                    . '   |   ' . $balLabel
                );

                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF1E3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 9.5, 'color' => ['argb' => 'FF666666']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(22);
                $sheet->getRowDimension(2)->setRowHeight(15);
                $sheet->freezePane('A4');
            },
        ];
    }
}
