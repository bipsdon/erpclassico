<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CustomBillController extends Controller
{
    public function create(): View
    {
        return view('billing.custom-bill');
    }

    public function download(Request $request): Response
    {
        $data = $request->validate([
            'bill_to'       => 'required|string|max:255',
            'bill_date'     => 'required|date',
            'bill_number'   => 'nullable|string|max:100',
            'notes'         => 'nullable|string|max:1000',
            'items'                => 'required|array|min:1',
            'items.*.title'        => 'required|string|max:255',
            'items.*.description'  => 'nullable|string|max:500',
            'items.*.qty'          => 'required|numeric|min:0',
            'items.*.price'        => 'required|numeric|min:0',
        ]);

        // Build computed line items
        $items = collect($data['items'])->map(function ($item) {
            return [
                'title'       => $item['title'],
                'description' => $item['description'] ?? '',
                'qty'         => (float) $item['qty'],
                'price'       => (float) $item['price'],
                'total'       => round((float) $item['qty'] * (float) $item['price'], 2),
            ];
        });

        $subtotal = $items->sum('total');

        $logoPath = public_path('images/classico-logo.png');

        $pdf = Pdf::loadView('billing.custom-bill-pdf', [
            'billTo'     => $data['bill_to'],
            'billDate'   => $data['bill_date'],
            'billNumber' => $data['bill_number'] ?? null,
            'notes'      => $data['notes'] ?? null,
            'items'      => $items,
            'subtotal'   => $subtotal,
            'logoPath'   => $logoPath,
        ])->setPaper('a4', 'portrait');

        $slug     = preg_replace('/[^a-z0-9]+/', '-', strtolower($data['bill_to']));
        $filename = 'bill-' . $slug . '-' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }
}
