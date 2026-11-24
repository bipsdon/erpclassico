<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientPaymentController extends Controller
{
    /**
     * Record a lump-sum payment from a client.
     * Automatically distributes received amount across outstanding bills,
     * oldest order first, until the payment is exhausted.
     */
    public function store(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'method'       => 'nullable|string|max:50',
            'reference'    => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:1000',
        ]);

        // Save the payment record
        ClientPayment::create([
            'client_id'    => $client->id,
            'amount'       => $data['amount'],
            'payment_date' => $data['payment_date'],
            'method'       => $data['method'] ?? null,
            'reference'    => $data['reference'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'recorded_by'  => auth()->id(),
        ]);

        // Distribute across outstanding bills — oldest order first
        $remaining = (float) $data['amount'];

        $orders = $client->orders()
            ->with('bill')
            ->whereHas('bill', fn ($q) => $q->whereRaw('received < amount'))
            ->orderBy('order_date')
            ->get();

        foreach ($orders as $order) {
            if ($remaining <= 0) break;

            $bill    = $order->bill;
            $owing   = $bill->amount - $bill->received;
            $toApply = min($remaining, $owing);

            $bill->received += $toApply;
            $bill->save();

            $remaining -= $toApply;
        }

        return redirect()->route('clients.show', $client)
            ->with('success', 'Payment of Rs. ' . number_format($data['amount'], 2) . ' recorded and applied to outstanding orders.');
    }

    public function destroy(Client $client, ClientPayment $payment): RedirectResponse
    {
        // Only allow deleting payments belonging to this client
        abort_unless($payment->client_id === $client->id, 404);

        $payment->delete();

        return redirect()->route('clients.show', $client)
            ->with('success', 'Payment record deleted.');
    }
}
