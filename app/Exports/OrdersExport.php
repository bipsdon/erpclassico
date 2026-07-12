<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements
    FromCollection,
    WithHeadings,
    WithTitle,
    WithStyles,
    WithColumnWidths,
    WithProperties
{
    public function __construct(private readonly Collection $orders) {}

    public function collection(): Collection
    {
        return $this->orders->map(function ($order, $index) {
            return [
                'no'               => $index + 1,
                'whatsapp_id'      => $order->whatsapp_order_id ?? '—',
                'order_number'     => $order->order_number,
                'customer_name'    => $order->customer_name,
                'customer_phone'   => $order->customer_phone ?? '—',
                'product_type'     => $order->product_type_label,
                'quantity'         => $order->quantity,
                'stage'            => $order->stage_label,
                'status'           => ucwords(str_replace('_', ' ', $order->status)),
                'priority'         => ucfirst($order->priority),
                'order_date'       => $order->order_date->format('d M Y'),
                'delivery_date'    => $order->delivery_date->format('d M Y'),
                'days_remaining'   => $order->stage === 'delivered'
                                        ? ($order->was_delivered_late
                                            ? 'Late by ' . $order->days_delivered_late . 'd'
                                            : 'On time')
                                        : ($order->is_late
                                            ? 'Overdue by ' . abs($order->days_remaining) . 'd'
                                            : $order->days_remaining . 'd remaining'),
                'players_count'    => $order->players_count ?? 0,
                'created_by'       => $order->creator->name ?? '—',
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'WhatsApp Order ID',
            'Order Number',
            'Customer Name',
            'Customer Phone',
            'Product Type',
            'Quantity',
            'Stage',
            'Status',
            'Priority',
            'Order Date',
            'Delivery Date',
            'Delivery Status',
            'Players',
            'Created By',
        ];
    }

    public function title(): string
    {
        return 'Orders';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E3A5F']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 22,
            'C' => 18,
            'D' => 24,
            'E' => 18,
            'F' => 16,
            'G' => 10,
            'H' => 16,
            'I' => 14,
            'J' => 12,
            'K' => 14,
            'L' => 14,
            'M' => 20,
            'N' => 10,
            'O' => 20,
        ];
    }

    public function properties(): array
    {
        return [
            'title'   => 'All Orders Export',
            'subject' => 'Orders — ' . now()->format('d M Y'),
            'creator' => config('app.name'),
        ];
    }
}
