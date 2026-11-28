<?php

namespace App\Http\Controllers;

use App\Exports\ClientLedgerExport;
use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = Client::withCount('orders')
            ->orderBy('name')
            ->paginate(30);

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'notes'   => 'nullable|string|max:2000',
        ]);

        $client = Client::create($data);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client "' . $client->name . '" created.');
    }

    public function show(Client $client): View
    {
        $orders = $client->orders()
            ->with(['bill', 'stageLogs'])
            ->orderBy('order_date')
            ->orderBy('id')
            ->get();

        $payments = $client->payments()
            ->with('recorder')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $totalBilled  = $orders->sum(fn ($o) => optional($o->bill)->amount ?? 0);
        $totalPaid    = $payments->sum('amount');
        $netBalance   = $totalBilled - $totalPaid;   // negative = client is in credit

        // Build a chronological event stream for the running-balance ledger.
        // Each event is either a debit (order billed) or a credit (payment received).
        $events = collect();

        foreach ($orders as $order) {
            $amount = optional($order->bill)->amount ?? 0;
            $events->push([
                'type'          => 'order',
                'date'          => $order->order_date,
                'sort_key'      => $order->order_date->format('Y-m-d') . '-A-' . $order->id,
                'order'         => $order,
                'debit'         => $amount,
                'credit'        => 0,
            ]);
        }

        foreach ($payments as $payment) {
            $events->push([
                'type'          => 'payment',
                'date'          => $payment->payment_date,
                'sort_key'      => $payment->payment_date->format('Y-m-d') . '-B-' . $payment->id,
                'payment'       => $payment,
                'debit'         => 0,
                'credit'        => $payment->amount,
            ]);
        }

        // Sort: by date, orders before payments on the same day (A before B in sort_key)
        $events = $events->sortBy('sort_key')->values();

        // Compute running balance for each event
        $running = 0;
        $events = $events->map(function ($event) use (&$running) {
            $running += $event['debit'] - $event['credit'];
            $event['running_balance'] = $running;
            return $event;
        });

        return view('clients.show', compact(
            'client', 'orders', 'payments', 'events',
            'totalBilled', 'totalPaid', 'netBalance'
        ));
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'notes'   => 'nullable|string|max:2000',
        ]);

        $client->update($data);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted.');
    }

    /**
     * JSON autocomplete endpoint: returns matching clients for the order form.
     */
    public function autocomplete(Request $request): \Illuminate\Http\JsonResponse
    {
        $term = $request->input('q', '');

        $clients = Client::where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'phone']);

        return response()->json($clients);
    }

    // ──────────────────────────────────────────────
    // Exports
    // ──────────────────────────────────────────────

    public function exportXlsx(Client $client): BinaryFileResponse
    {
        $filename = 'ledger-' . str($client->name)->slug() . '-' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new ClientLedgerExport($client), $filename);
    }

    public function exportPdf(Client $client): Response
    {
        $orders   = $client->orders()->with(['bill', 'stageLogs'])->orderBy('order_date')->orderBy('id')->get();
        $payments = $client->payments()->orderBy('payment_date')->orderBy('id')->get();

        $totalBilled = $orders->sum(fn ($o) => optional($o->bill)->amount ?? 0);
        $totalPaid   = $payments->sum('amount');
        $netBalance  = $totalBilled - $totalPaid;

        // Build the same event stream as show()
        $events = collect();
        foreach ($orders as $order) {
            $events->push([
                'type'     => 'order',
                'sort_key' => $order->order_date->format('Y-m-d') . '-A-' . $order->id,
                'order'    => $order,
                'debit'    => optional($order->bill)->amount ?? 0,
                'credit'   => 0,
            ]);
        }
        foreach ($payments as $payment) {
            $events->push([
                'type'     => 'payment',
                'sort_key' => $payment->payment_date->format('Y-m-d') . '-B-' . $payment->id,
                'payment'  => $payment,
                'debit'    => 0,
                'credit'   => $payment->amount,
            ]);
        }
        $events = $events->sortBy('sort_key')->values();
        $running = 0;
        $events = $events->map(function ($event) use (&$running) {
            $running += $event['debit'] - $event['credit'];
            $event['running_balance'] = $running;
            return $event;
        });

        $pdf = Pdf::loadView('clients.ledger-pdf', compact(
            'client', 'events', 'totalBilled', 'totalPaid', 'netBalance'
        ))->setPaper('a4', 'landscape');

        $filename = 'ledger-' . str($client->name)->slug() . '-' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }
}
