<?php

namespace App\Exports;

use App\Models\Client;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
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
    private Collection $orders;
    private float $totalBilled;
    private float $totalReceived;
    private float $totalBalance;

    public function __construct(private readonly Client $client)
    {
        $this->orders = $client->orders()
            ->with(['bill', 'stageLogs'])
            ->orderBy('order_date')
            ->get();

        $this->totalBilled   = $this->orders->sum(fn ($o) => optional($o->bill)->amount   ?? 0);
        $this->totalReceived = $this->orders->sum(fn ($o) => optional($o->bill)->received ?? 0);
        $this->totalBalance  = $this->totalBilled - $this->totalReceived;
    }

    public function collection(): Collection
    {
        $rows = $this->orders->map(function ($order, $index) {
            $bill     = $order->bill;
            $amount   = $bill?->amount   ?? 0;
            $received = $bill?->received ?? 0;
            $balance  = $amount - $received;

            $deliveredLog  = $order->stageLogs->first(fn ($l) => $l->to_stage === 'delivered');
            $deliveredDate = $deliveredLog?->created_at?->format('d M Y') ?? '—';

            return [
                'no'               => $index + 1,
                'order_number'     => $order->order_number,
                'whatsapp_order_id'=> $order->whatsapp_order_id ?? '—',
                'order_date'       => $order->order_date->format('d M Y'),
                'delivered_date'   => $deliveredDate,
                'stage'            => $order->stage_label,
                'amount'           => $amount > 0 ? $amount : '',
                'received'         => $received > 0 ? $received : '',
                'balance'          => $amount > 0 ? $balance : '',
                'notes'            => $bill?->notes ?? '',
            ];
        });

        // Totals row
        $rows->push([
            'no'               => '',
            'order_number'     => '',
            'whatsapp_order_id'=> '',
            'order_date'       => '',
            'delivered_date'   => '',
            'stage'            => 'TOTAL',
            'amount'           => $this->totalBilled,
            'received'         => $this->totalReceived,
            'balance'          => $this->totalBalance,
            'notes'            => '',
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            '#',
            'Order Number',
            'WhatsApp Order ID',
            'Order Date',
            'Delivered Date',
            'Stage',
            'Amount (Rs.)',
            'Received (Rs.)',
            'Balance (Rs.)',
            'Notes',
        ];
    }

    public function title(): string
    {
        return 'Ledger';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->orders->count() + 2; // +1 header, +1 totals

        // Header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        // Totals row
        $totalsStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEEF2FF']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ];

        // Number columns right-aligned
        $numberStyle = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]];

        return [
            1           => $headerStyle,
            $lastRow    => $totalsStyle,
            'G'         => $numberStyle,
            'H'         => $numberStyle,
            'I'         => $numberStyle,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 18,
            'C' => 22,
            'D' => 14,
            'E' => 16,
            'F' => 16,
            'G' => 14,
            'H' => 14,
            'I' => 14,
            'J' => 30,
        ];
    }

    public function properties(): array
    {
        return [
            'title'   => $this->client->name . ' — Ledger',
            'subject' => 'Client Ledger — ' . now()->format('d M Y'),
            'creator' => config('app.name'),
        ];
    }

    public function registerEvents(): array
    {
        $client = $this->client;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($client) {
                $sheet = $event->sheet->getDelegate();

                // Insert two rows at top for client info header
                $sheet->insertNewRowBefore(1, 2);

                $sheet->setCellValue('A1', $client->name . ' — Client Ledger');
                $sheet->setCellValue('A2', 'Generated: ' . now()->format('d M Y H:i')
                    . ($client->phone ? '   |   Phone: ' . $client->phone : ''));

                $sheet->mergeCells('A1:J1');
                $sheet->mergeCells('A2:J2');

                $titleStyle = [
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1E3A5F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ];
                $subStyle = [
                    'font' => ['size' => 10, 'color' => ['argb' => 'FF666666']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ];

                $sheet->getStyle('A1')->applyFromArray($titleStyle);
                $sheet->getStyle('A2')->applyFromArray($subStyle);
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(16);

                // Freeze pane below the two header rows + column heading row
                $sheet->freezePane('A4');
            },
        ];
    }
}
