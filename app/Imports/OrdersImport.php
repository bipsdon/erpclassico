<?php

namespace App\Imports;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderPlayer;
use App\Models\OrderStageLog;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/**
 * Imports orders (and optional player rows) from a CSV or XLSX file.
 *
 * Expected columns (case-insensitive, spaces/underscores interchangeable):
 *
 *   customer_name        required
 *   customer_phone       required
 *   quantity             required  integer ≥ 1
 *   product_type         required  jersey|tracksuit|polo_shirt|shorts|other
 *   order_date           required  YYYY-MM-DD
 *   delivery_date        required  YYYY-MM-DD ≥ order_date
 *   priority             optional  normal|rush|critical  (default: normal)
 *   pipeline             optional  comma-separated: design,print,sew (default: design,print,sew)
 *   whatsapp_order_id    optional
 *   notes                optional
 *
 * Player columns (all optional, as many sets as needed):
 *   player_1_name, player_1_number, player_1_size, player_1_notes
 *   player_2_name, player_2_number, ... (up to 100)
 */
class OrdersImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /** @var array<int,string>  Validation errors keyed by "Row N: field" */
    public array $errors = [];

    /** @var int  Number of orders successfully created */
    public int $imported = 0;

    public function __construct(private readonly int $createdBy) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $rowIndex => $row) {
            $rowNum = $rowIndex + 2; // +2: 1-indexed + heading row
            $data   = $this->normalise($row->toArray());

            // ── Validate row ──────────────────────────────────
            $validator = Validator::make($data, $this->rowRules(), $this->rowMessages());

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    foreach ($messages as $msg) {
                        $this->errors[] = "Row {$rowNum} ({$data['customer_name']}): {$msg}";
                    }
                }
                continue; // skip bad row, process the rest
            }

            $validated = $validator->validated();

            // ── Create order ──────────────────────────────────
            try {
                DB::transaction(function () use ($validated, $rowNum) {
                    $pipeline = $this->parsePipeline($validated['pipeline'] ?? '');

                    $details = isset($validated['details'])
                        ? StoreOrderRequest::sanitiseRichText($validated['details'])
                        : null;

                    $order = Order::create([
                        'customer_name'      => $validated['customer_name'],
                        'customer_phone'     => $validated['customer_phone'],
                        'whatsapp_order_id'  => $validated['whatsapp_order_id'] ?? null,
                        'quantity'           => (int) $validated['quantity'],
                        'product_type'       => $validated['product_type'],
                        'order_date'         => $validated['order_date'],
                        'delivery_date'      => $validated['delivery_date'],
                        'priority'           => $validated['priority'] ?? 'normal',
                        'pipeline'           => $pipeline,
                        'notes'              => $validated['notes'] ?? null,
                        'details'            => $details,
                        'stage'              => 'design',
                        'status'             => 'pending',
                        'created_by'         => $this->createdBy,
                    ]);

                    // ── Players ───────────────────────────────
                    $this->createPlayers($order, $validated);

                    OrderStageLog::create([
                        'order_id'    => $order->id,
                        'from_stage'  => null,
                        'to_stage'    => 'design',
                        'from_status' => null,
                        'to_status'   => 'pending',
                        'changed_by'  => $this->createdBy,
                        'notes'       => 'Created via bulk import.',
                    ]);

                    $this->imported++;
                });
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum}: Unexpected error — " . $e->getMessage();
            }
        }

        if ($this->imported > 0) {
            SchedulingService::clearScheduleCache();
        }
    }

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

    /**
     * Normalise column keys: lowercase, collapse spaces/dashes to underscores.
     * This makes "Customer Name", "customer-name", "CustomerName" all work.
     */
    private function normalise(array $raw): array
    {
        $out = [];
        foreach ($raw as $key => $value) {
            $normKey = strtolower(preg_replace('/[\s\-]+/', '_', trim((string) $key)));
            // Treat empty-ish values as null
            $out[$normKey] = (is_string($value) && trim($value) === '') ? null : $value;
        }
        return $out;
    }

    /**
     * Per-row validation rules.
     */
    private function rowRules(): array
    {
        return [
            'customer_name'      => ['required', 'string', 'max:150'],
            'customer_phone'     => ['required', 'string', 'max:30'],
            'whatsapp_order_id'  => ['nullable', 'string', 'max:100'],
            'quantity'           => ['required', 'integer', 'min:1', 'max:9999'],
            'product_type'       => ['required', 'string', 'in:jersey,tracksuit,polo_shirt,shorts,other'],
            'order_date'         => ['required', 'date'],
            'delivery_date'      => ['required', 'date', 'after_or_equal:order_date'],
            'priority'           => ['nullable', 'in:normal,rush,critical'],
            'pipeline'           => ['nullable', 'string'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'details'            => ['nullable', 'string'],
        ];
    }

    private function rowMessages(): array
    {
        return [
            'delivery_date.after_or_equal' => 'Delivery date must be on or after the order date.',
            'product_type.in'              => 'Product type must be jersey, tracksuit, polo_shirt, shorts, or other.',
            'priority.in'                  => 'Priority must be normal, rush, or critical.',
        ];
    }

    /**
     * Parse "design,print,sew" → ['design','print','sew'].
     * Falls back to full pipeline if blank or invalid.
     */
    private function parsePipeline(?string $raw): array
    {
        $valid = ['design', 'print', 'sew'];

        if (!$raw) {
            return $valid;
        }

        $stages = array_filter(
            array_map('trim', explode(',', $raw)),
            fn ($s) => in_array($s, $valid, true)
        );

        // Design must always be first
        if (empty($stages) || $stages[0] !== 'design') {
            array_unshift($stages, 'design');
        }

        return array_values(array_unique($stages));
    }

    /**
     * Extract player_1_name … player_N_name from the validated row and
     * create OrderPlayer records.
     */
    private function createPlayers(Order $order, array $data): void
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'];

        for ($n = 1; $n <= 100; $n++) {
            $name = $data["player_{$n}_name"] ?? null;
            if (!$name) break;

            $rawSize = strtoupper(trim((string) ($data["player_{$n}_size"] ?? '')));

            $order->players()->create([
                'player_name'   => $name,
                'jersey_number' => $data["player_{$n}_number"] ?? '',
                'size'          => in_array($rawSize, $sizes, true) ? $rawSize : null,
                'notes'         => $data["player_{$n}_notes"] ?? null,
                'sort_order'    => $n - 1,
            ]);
        }
    }
}
