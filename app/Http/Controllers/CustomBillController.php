<?php

namespace App\Http\Controllers;

use App\Models\CustomBill;
use App\Models\CustomBillItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomBillController extends Controller
{
    // ── List all saved bills ──────────────────────────────────

    public function index(): View
    {
        $bills = CustomBill::with('creator')
            ->orderByDesc('id')
            ->paginate(25);

        return view('billing.index', compact('bills'));
    }

    // ── Build form (create new) ───────────────────────────────

    public function create(): View
    {
        // Show what the next invoice number will look like (read-only preview)
        $prefix     = 'INV-' . now()->format('Ym') . '-';
        $last       = CustomBill::where('invoice_number', 'like', $prefix . '%')
                        ->orderByDesc('id')
                        ->value('invoice_number');
        $nextSeq    = $last ? ((int) substr($last, -4)) + 1 : 1;
        $nextNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        return view('billing.custom-bill', compact('nextNumber'));
    }

    // ── Save + download PDF ───────────────────────────────────

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'bill_to'              => 'required|string|max:255',
            'bill_date'            => 'required|date',
            'notes'                => 'nullable|string|max:1000',
            'items'                => 'required|array|min:1',
            'items.*.title'        => 'required|string|max:255',
            'items.*.description'  => 'nullable|string|max:500',
            'items.*.qty'          => 'required|numeric|min:0',
            'items.*.price'        => 'required|numeric|min:0',
        ]);

        // Build computed line items
        $items = collect($data['items'])->values()->map(function ($item, $i) {
            return [
                'title'       => $item['title'],
                'description' => $item['description'] ?? '',
                'qty'         => (float) $item['qty'],
                'price'       => (float) $item['price'],
                'total'       => round((float) $item['qty'] * (float) $item['price'], 2),
                'sort_order'  => $i,
            ];
        });

        $subtotal = $items->sum('total');

        // Persist bill + items in a transaction (auto-generates invoice_number)
        $bill = DB::transaction(function () use ($data, $items, $subtotal) {
            $bill = CustomBill::create([
                'bill_to'    => $data['bill_to'],
                'bill_date'  => $data['bill_date'],
                'subtotal'   => $subtotal,
                'notes'      => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                CustomBillItem::create(array_merge($item, ['custom_bill_id' => $bill->id]));
            }

            return $bill;
        });

        return $this->buildPdf($bill->load('items'));
    }

    // ── Re-download an existing saved bill ────────────────────

    public function download(CustomBill $bill): Response
    {
        return $this->buildPdf($bill->load('items'));
    }

    // ── Delete a saved bill ───────────────────────────────────

    public function destroy(CustomBill $bill): RedirectResponse
    {
        $bill->delete();

        return redirect()->route('billing.index')
            ->with('success', 'Bill ' . $bill->invoice_number . ' deleted.');
    }

    // ── Internal: build and stream the PDF ───────────────────

    private function buildPdf(CustomBill $bill): Response
    {
        $logoPath = public_path('images/classico-logo.png');

        $items = $bill->items->map(fn ($it) => [
            'title'       => $it->title,
            'description' => $it->description,
            'qty'         => $it->qty,
            'price'       => $it->price,
            'total'       => $it->total,
        ]);

        $pdf = Pdf::loadView('billing.custom-bill-pdf', [
            'billTo'     => $bill->bill_to,
            'billDate'   => $bill->bill_date->toDateString(),
            'billNumber' => $bill->invoice_number,
            'notes'      => $bill->notes,
            'items'      => $items,
            'subtotal'   => $bill->subtotal,
            'logoPath'   => $logoPath,
        ])->setPaper('a4', 'portrait');

        $filename = $bill->invoice_number . '-' .
                    preg_replace('/[^a-z0-9]+/', '-', strtolower($bill->bill_to)) . '.pdf';

        return $pdf->download($filename);
    }
}
