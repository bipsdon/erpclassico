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
     * The payment is saved as an audit record. The client's balance
     * is calculated as: totalBilled - totalPaid (sum of all ClientPayments).
     * We no longer distribute into bill.received — that field is only for
     * the per-order bill PDF and is set manually via the bill modal.
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

        ClientPayment::create([
            'client_id'    => $client->id,
            'amount'       => $data['amount'],
            'payment_date' => $data['payment_date'],
            'method'       => $data['method'] ?? null,
            'reference'    => $data['reference'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'recorded_by'  => auth()->id(),
        ]);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Payment of Rs. ' . number_format($data['amount'], 2) . ' recorded.');
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
