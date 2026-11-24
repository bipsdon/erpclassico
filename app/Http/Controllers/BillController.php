<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BillController extends Controller
{
    /**
     * Save (create or update) the bill for an order.
     * One bill per order — upsert on order_id.
     */
    public function upsert(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'amount'   => 'required|numeric|min:0',
            'received' => 'required|numeric|min:0',
            'notes'    => 'nullable|string|max:1000',
        ]);

        $bill = $order->bill ?? new Bill(['order_id' => $order->id]);

        // Received cannot exceed amount
        if ($data['received'] > $data['amount']) {
            return back()->withErrors(['received' => 'Amount received cannot exceed the bill amount.'])->withInput();
        }

        $bill->fill([
            'amount'     => $data['amount'],
            'received'   => $data['received'],
            'notes'      => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
        $bill->order_id = $order->id;
        $bill->save();

        return back()->with('success', 'Bill saved.');
    }

    /**
     * Download a PDF bill for an order.
     * Works even before a bill exists — shows "Pending billing" in that case.
     */
    public function exportPdf(Order $order): Response
    {
        $order->loadMissing(['bill', 'players']);

        $bill = $order->bill;

        // Drop a logo PNG at public/images/classico-logo.png to have it appear on the bill.
        $logoPath = public_path('images/classico-logo.png');

        $pdf = Pdf::loadView('orders.bill-pdf', compact('order', 'bill', 'logoPath'))
            ->setPaper('a4', 'portrait');

        $slug     = str_replace(['/', '\\', ' '], '-', $order->order_number);
        $filename = 'bill-' . $slug . '.pdf';

        return $pdf->download($filename);
    }
}
