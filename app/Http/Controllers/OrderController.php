<?php

namespace App\Http\Controllers;

use App\Exports\OrderPlayersExport;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\OrderAttachment;
use App\Models\OrderPlayer;
use App\Models\OrderStageLog;
use App\Services\Scheduling\SchedulingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    public function __construct(private readonly SchedulingService $scheduler) {}

    // ──────────────────────────────────────────────
    // Index
    // ──────────────────────────────────────────────

    public function index(Request $request): View
    {
        $query = Order::with('creator', 'stageLogs')
            ->withCount('players');

        // Filter by stage
        if ($stage = $request->input('stage')) {
            $query->where('stage', $stage);
        }

        // Filter by priority
        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Search by order number, whatsapp id, or customer name/phone
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('whatsapp_order_id', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Sortable columns whitelist: query param => DB column(s)
        $sortable = [
            'order_number'  => ['order_number'],
            'customer'      => ['customer_name'],
            'quantity'      => ['quantity'],
            'product_type'  => ['product_type'],
            'stage'         => ['stage'],
            'priority'      => ['priority'],
            'status'        => ['status'],
            'delivery_date' => ['delivery_date'],
        ];

        $sortKey = $request->input('sort', 'default');
        $sortDir = $request->input('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if ($sortKey !== 'default' && isset($sortable[$sortKey])) {
            foreach ($sortable[$sortKey] as $col) {
                $query->orderBy($col, $sortDir);
            }
        } else {
            // Default: critical → rush → normal, then earliest delivery first
            $query->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'rush' THEN 1 ELSE 2 END")
                  ->orderBy('delivery_date');
        }

        $orders = $query->paginate(25)->withQueryString();

        $stageCounts = Order::selectRaw('stage, count(*) as total')
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('stage')
            ->pluck('total', 'stage');

        return view('orders.index', compact('orders', 'stageCounts', 'sortKey', 'sortDir'));
    }

    // ──────────────────────────────────────────────
    // Create / Store
    // ──────────────────────────────────────────────

    public function create(): View
    {
        $this->authorizeManager();

        $order = null;

        return view('orders.create', compact('order'));
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $order = Order::create([
                ...$request->only([
                    'customer_name', 'customer_phone', 'whatsapp_order_id', 'quantity', 'product_type',
                    'order_date', 'delivery_date', 'priority',
                    'details', 'notes',
                ]),
                'stage'      => 'design',
                'pipeline'   => $request->input('pipeline', ['design', 'print', 'sew']),
                'status'     => 'pending',
                'created_by' => auth()->id(),
            ]);

            $this->syncPlayers($order, $request->input('players', []));
            $this->storeAttachments($order, $request);

            OrderStageLog::create([
                'order_id'    => $order->id,
                'from_stage'  => null,
                'to_stage'    => 'design',
                'from_status' => null,
                'to_status'   => 'pending',
                'changed_by'  => auth()->id(),
                'notes'       => 'Order created.',
            ]);
        });

        SchedulingService::clearScheduleCache();

        return redirect()->route('orders.index')
            ->with('success', 'Order created successfully.');
    }

    // ──────────────────────────────────────────────
    // Show
    // ──────────────────────────────────────────────

    public function show(Order $order): View
    {
        $order->load(['creator', 'players', 'attachments.uploader', 'stageLogs.changedBy', 'productionSchedules']);

        return view('orders.show', compact('order'));
    }

    // ──────────────────────────────────────────────
    // Edit / Update
    // ──────────────────────────────────────────────

    public function edit(Order $order): View
    {
        $this->authorizeManager();

        $order->load(['players', 'attachments']);

        return view('orders.edit', compact('order'));
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($request, $order) {
            $fromStage  = $order->stage;
            $fromStatus = $order->status;

            $order->update($request->only([
                'customer_name', 'customer_phone', 'whatsapp_order_id', 'quantity', 'product_type',
                'order_date', 'delivery_date', 'priority',
                'stage', 'pipeline', 'status', 'details', 'notes',
            ]));

            $this->syncPlayers($order, $request->input('players', []));
            $this->storeAttachments($order, $request);

            // Log stage/status changes
            if ($fromStage !== $order->stage || $fromStatus !== $order->status) {
                OrderStageLog::create([
                    'order_id'    => $order->id,
                    'from_stage'  => $fromStage,
                    'to_stage'    => $order->stage,
                    'from_status' => $fromStatus,
                    'to_status'   => $order->status,
                    'changed_by'  => auth()->id(),
                    'notes'       => 'Updated via order edit.',
                ]);
            }
        });

        SchedulingService::clearScheduleCache();

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order updated successfully.');
    }

    // ──────────────────────────────────────────────
    // Duplicate
    // ──────────────────────────────────────────────

    public function duplicate(Order $order): RedirectResponse
    {
        $this->authorizeManager();

        $order->load('players');

        DB::transaction(function () use ($order) {
            $newOrder = Order::create([
                'customer_name'     => $order->customer_name,
                'customer_phone'    => $order->customer_phone,
                'whatsapp_order_id' => $order->whatsapp_order_id,
                'quantity'          => $order->quantity,
                'product_type'      => $order->product_type,
                'order_date'        => now()->toDateString(),
                'delivery_date'     => $order->delivery_date->toDateString(),
                'priority'          => $order->priority,
                'details'           => $order->details,
                'notes'             => $order->notes,
                'stage'             => 'design',
                'pipeline'          => $order->effectivePipeline(),
                'status'            => 'pending',
                'created_by'        => auth()->id(),
            ]);

            // Duplicate players list (strip IDs so new rows are created)
            foreach ($order->players as $player) {
                $newOrder->players()->create([
                    'player_name'   => $player->player_name,
                    'jersey_number' => $player->jersey_number,
                    'size'          => $player->size,
                    'notes'         => $player->notes,
                    'sort_order'    => $player->sort_order,
                ]);
            }

            // Attachments are NOT copied — files on disk are tied to the
            // original order folder and should be re-uploaded if needed.

            OrderStageLog::create([
                'order_id'    => $newOrder->id,
                'from_stage'  => null,
                'to_stage'    => 'design',
                'from_status' => null,
                'to_status'   => 'pending',
                'changed_by'  => auth()->id(),
                'notes'       => "Duplicated from order {$order->order_number}.",
            ]);
        });

        SchedulingService::clearScheduleCache();

        $ref = $order->whatsapp_order_id ?? $order->order_number;

        return redirect()->route('orders.index')
            ->with('success', "Order {$ref} duplicated successfully. New order created and placed in Design queue.");
    }

    // ──────────────────────────────────────────────
    // Destroy
    // ──────────────────────────────────────────────

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorizeManager();

        // Delete stored files
        foreach ($order->attachments as $attachment) {
            Storage::disk('private')->delete($attachment->file_path);
        }

        $order->delete(); // soft delete

        SchedulingService::clearScheduleCache();

        $ref = $order->whatsapp_order_id ?? $order->order_number;

        return redirect()->route('orders.index')
            ->with('success', "Order {$ref} deleted.");
    }

    // ──────────────────────────────────────────────
    // Exports
    // ──────────────────────────────────────────────

    public function exportPdf(Order $order): Response
    {
        $order->load(['creator', 'players', 'attachments.uploader', 'stageLogs.changedBy', 'productionSchedules']);

        $pdf = Pdf::loadView('orders.pdf', compact('order'))
            ->setPaper('a4', 'portrait');

        $filename = ($order->whatsapp_order_id ?? $order->order_number) . '.pdf';

        return $pdf->download($filename);
    }

    public function exportXlsx(Order $order): BinaryFileResponse
    {
        $order->load('players');

        $filename = ($order->whatsapp_order_id ?? $order->order_number) . '-players.xlsx';

        return Excel::download(
            new OrderPlayersExport($order),
            $filename
        );
    }

    // ──────────────────────────────────────────────
    // Attachment download
    // ──────────────────────────────────────────────
    public function downloadAttachment(Order $order, OrderAttachment $attachment): mixed
    {
        abort_if($attachment->order_id !== $order->id, 404);

        return Storage::disk('private')->download(
            $attachment->file_path,
            $attachment->original_name
        );
    }

    public function deleteAttachment(Order $order, OrderAttachment $attachment): RedirectResponse
    {
        $this->authorizeManager();

        abort_if($attachment->order_id !== $order->id, 404);

        Storage::disk('private')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Attachment deleted.');
    }

    // ──────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────

    /**
     * Sync the players list: delete removed rows, upsert existing/new.
     */
    private function syncPlayers(Order $order, array $players): void
    {
        // Delete players not included in the submitted list
        $submittedIds = collect($players)->pluck('id')->filter()->values()->all();

        $order->players()
            ->whereNotIn('id', $submittedIds)
            ->delete();

        foreach ($players as $index => $row) {
            if (empty($row['player_name'])) {
                continue;
            }

            $data = [
                'player_name'   => $row['player_name'],
                'jersey_number' => $row['jersey_number'] ?? '',
                'size'          => $row['size']          ?? null,
                'notes'         => $row['notes']         ?? null,
                'sort_order'    => $index,
            ];

            if (! empty($row['id'])) {
                OrderPlayer::where('id', $row['id'])
                    ->where('order_id', $order->id)
                    ->update($data);
            } else {
                $order->players()->create($data);
            }
        }
    }

    /**
     * Store any uploaded attachments for the order.
     */
    private function storeAttachments(Order $order, Request $request): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path       = "attachments/{$order->id}/{$storedName}";

            Storage::disk('private')->putFileAs(
                "attachments/{$order->id}",
                $file,
                $storedName
            );

            OrderAttachment::create([
                'order_id'      => $order->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $storedName,
                'file_path'     => $path,
                'mime_type'     => $file->getMimeType(),
                'file_size'     => $file->getSize(),
                'uploaded_by'   => auth()->id(),
            ]);
        }
    }

    /**
     * Abort with 403 if current user is not a pipeline manager.
     */
    private function authorizeManager(): void
    {
        abort_unless(auth()->user()->isPipelineManager(), 403, 'Only Pipeline Managers can perform this action.');
    }
}
