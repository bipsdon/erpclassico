<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
}
