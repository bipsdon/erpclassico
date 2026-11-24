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
        $client->load(['orders.bill']);

        // Orders with their bill, newest first
        $orders = $client->orders()
            ->with('bill')
            ->orderByDesc('order_date')
            ->get();

        $totalBilled   = $orders->sum(fn ($o) => optional($o->bill)->amount ?? 0);
        $totalReceived = $orders->sum(fn ($o) => optional($o->bill)->received ?? 0);
        $totalBalance  = $totalBilled - $totalReceived;

        return view('clients.show', compact('client', 'orders', 'totalBilled', 'totalReceived', 'totalBalance'));
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
        $client->load(['orders.bill', 'orders.stageLogs']);

        $orders        = $client->orders()->with(['bill', 'stageLogs'])->orderBy('order_date')->get();
        $totalBilled   = $orders->sum(fn ($o) => optional($o->bill)->amount   ?? 0);
        $totalReceived = $orders->sum(fn ($o) => optional($o->bill)->received ?? 0);
        $totalBalance  = $totalBilled - $totalReceived;

        $pdf = Pdf::loadView('clients.ledger-pdf', compact('client', 'orders', 'totalBilled', 'totalReceived', 'totalBalance'))
            ->setPaper('a4', 'landscape');

        $filename = 'ledger-' . str($client->name)->slug() . '-' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }
}
