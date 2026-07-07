<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HistoryExport implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithStyles,
    WithColumnWidths,
    WithProperties
{
    public function __construct(
        private readonly Collection $schedules,
        private readonly string     $department,
        private readonly Carbon     $from,
        private readonly Carbon     $to,
    ) {}

    public function collection(): Collection
    {
        return $this->schedules->map(function ($schedule, $index) {
            $order = $schedule->order;

            return [
                'no'              => $index + 1,
                'completed_at'    => Carbon::parse($schedule->completed_at)->format('d M Y H:i'),
                'department'      => ucfirst($schedule->department),
                'order_number'    => $order->order_number,
                'whatsapp_id'     => $order->whatsapp_order_id ?? '—',
                'customer_name'   => $order->customer_name,
                'customer_phone'  => $order->customer_phone ?? '—',
                'product_type'    => $order->product_type_label,
                'quantity'        => $order->quantity,
                'qty_scheduled'   => $schedule->quantity_scheduled,
                'priority'        => ucfirst($order->priority),
                'order_date'      => $order->order_date->format('d M Y'),
                'delivery_date'   => $order->delivery_date->format('d M Y'),
                'stage'           => $order->stage_label,
                'status'          => ucwords(str_replace('_', ' ', $order->status)),
                'is_overtime'     => $schedule->is_overtime ? 'Yes' : 'No',
                'completed_by'    => $schedule->completedByUser->name ?? '—',
                'created_by'      => $order->creator->name ?? '—',
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'Completed At',
            'Department',
            'Order Number',
            'WhatsApp Order ID',
            'Customer Name',
            'Customer Phone',
            'Product Type',
            'Order Qty',
            'Dept Qty',
            'Priority',
            'Order Date',
            'Delivery Date',
            'Current Stage',
            'Status',
            'Overtime?',
            'Completed By',
            'Created By',
        ];
    }

    public function title(): string
    {
        $dept = $this->department === 'all' ? 'All Departments' : ucfirst($this->department);
        return "{$dept} History";
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E3A5F']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 20,
            'C' => 14,
            'D' => 18,
            'E' => 22,
            'F' => 22,
            'G' => 18,
            'H' => 16,
            'I' => 12,
            'J' => 12,
            'K' => 12,
            'L' => 14,
            'M' => 14,
            'N' => 16,
            'O' => 14,
            'P' => 12,
            'Q' => 20,
            'R' => 20,
        ];
    }

    public function properties(): array
    {
        $dept  = $this->department === 'all' ? 'All Departments' : ucfirst($this->department);
        $range = $this->from->format('d M Y') . ' – ' . $this->to->format('d M Y');

        return [
            'title'   => "Production History — {$dept}",
            'subject' => "History report: {$range}",
            'creator' => config('app.name'),
        ];
    }
}
