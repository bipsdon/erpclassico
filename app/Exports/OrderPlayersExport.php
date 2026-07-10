<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderPlayersExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(private readonly Order $order) {}

    public function collection()
    {
        return $this->order->players->map(fn ($p, $i) => [
            'no'            => $i + 1,
            'player_name'   => $p->player_name,
            'jersey_number' => $p->jersey_number,
            'size'          => $p->size ?? '—',
            'notes'         => $p->notes ?? '',
        ]);
    }

    public function headings(): array
    {
        return ['#', 'Player Name', 'Jersey Number', 'Size', 'Notes'];
    }

    public function title(): string
    {
        return $this->order->whatsapp_order_id ?? $this->order->order_number;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
