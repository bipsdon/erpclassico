<?php

namespace App\Http\Controllers;

use App\Imports\OrdersImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderImportController extends Controller
{
    // ──────────────────────────────────────────────
    // Show the import form
    // ──────────────────────────────────────────────

    public function create(): View
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        return view('orders.import');
    }

    // ──────────────────────────────────────────────
    // Process the uploaded file
    // ──────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        $request->validate([
            'import_file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240', // 10 MB
            ],
        ], [
            'import_file.required' => 'Please choose a file to upload.',
            'import_file.mimes'    => 'Only XLSX, XLS, and CSV files are accepted.',
            'import_file.max'      => 'The file must be under 10 MB.',
        ]);

        $import = new OrdersImport(auth()->id());

        Excel::import($import, $request->file('import_file'));

        if ($import->imported === 0 && empty($import->errors)) {
            return back()->with('warning', 'The file was empty or contained no valid rows.');
        }

        $messages = [];

        if ($import->imported > 0) {
            $messages['success'] = "Successfully imported {$import->imported} order(s).";
        }

        if (!empty($import->errors)) {
            // Flash the row errors into the session so the view can display them
            session()->flash('import_errors', $import->errors);

            if ($import->imported > 0) {
                $messages['warning'] = count($import->errors) . ' row(s) had errors and were skipped (see details below).';
            } else {
                $messages['error'] = 'No orders were imported — all rows had errors (see details below).';
            }
        }

        return redirect()->route('orders.import.create')->with($messages);
    }

    // ──────────────────────────────────────────────
    // Download blank template
    // ──────────────────────────────────────────────

    public function template(): BinaryFileResponse
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        $path = storage_path('app/templates/orders_import_template.xlsx');

        if (!file_exists($path)) {
            $this->generateTemplate($path);
        }

        return response()->download($path, 'orders_import_template.xlsx');
    }

    // ──────────────────────────────────────────────
    // Build & cache the template XLSX
    // ──────────────────────────────────────────────

    private function generateTemplate(string $path): void
    {
        @mkdir(dirname($path), 0755, true);

        // We write the template using the PhpSpreadsheet API that
        // ships inside maatwebsite/excel — no extra dependency needed.
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Orders');

        // ── Header row ───────────────────────────────────────
        $headers = [
            'customer_name',
            'customer_phone',
            'quantity',
            'product_type',
            'order_date',
            'delivery_date',
            'priority',
            'pipeline',
            'whatsapp_order_id',
            'notes',
            'player_1_name',
            'player_1_number',
            'player_1_size',
            'player_1_notes',
            'player_2_name',
            'player_2_number',
            'player_2_size',
            'player_2_notes',
            // ... continue the pattern for more players
        ];

        foreach ($headers as $col => $heading) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $heading);
        }

        // Bold header row
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9E1F2');

        // ── Example data row ─────────────────────────────────
        $example = [
            'FC United Academy',         // customer_name
            '+60 12-345 6789',           // customer_phone
            22,                          // quantity
            'jersey',                    // product_type
            now()->format('Y-m-d'),      // order_date
            now()->addDays(14)->format('Y-m-d'), // delivery_date
            'normal',                    // priority
            'design,print,sew',          // pipeline
            'WA-20260101-001',           // whatsapp_order_id
            'Rush job, check specs',     // notes
            'Ali Bin Abu',               // player_1_name
            '10',                        // player_1_number
            'L',                         // player_1_size
            '',                          // player_1_notes
            'Hafiz Rahman',              // player_2_name
            '7',                         // player_2_number
            'M',                         // player_2_size
            '',                          // player_2_notes
        ];

        foreach ($example as $col => $value) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '2';
            $sheet->setCellValue($cell, $value);
        }

        // Auto-fit columns
        foreach (range('A', $lastCol) as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // ── Instructions sheet ────────────────────────────────
        $info = $spreadsheet->createSheet();
        $info->setTitle('Instructions');
        $info->setCellValue('A1', 'Column Reference');
        $info->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $instructions = [
            ['Column', 'Required', 'Allowed values / format'],
            ['customer_name',      'Yes', 'Text, max 150 characters'],
            ['customer_phone',     'Yes', 'Text, max 30 characters'],
            ['quantity',           'Yes', 'Integer 1–9999'],
            ['product_type',       'Yes', 'jersey · tracksuit · polo_shirt · shorts · other'],
            ['order_date',         'Yes', 'YYYY-MM-DD  (e.g. 2026-11-15)'],
            ['delivery_date',      'Yes', 'YYYY-MM-DD, must be ≥ order_date'],
            ['priority',           'No',  'normal · rush · critical  (default: normal)'],
            ['pipeline',           'No',  'Comma-separated: design,print,sew  (default: all three)'],
            ['whatsapp_order_id',  'No',  'Text, max 100 characters'],
            ['notes',              'No',  'Text, max 2000 characters'],
            ['player_N_name',      'No',  'Player name — repeat for N = 1,2,3…'],
            ['player_N_number',    'No',  'Jersey number'],
            ['player_N_size',      'No',  'XS · S · M · L · XL · XXL · 3XL'],
            ['player_N_notes',     'No',  'Special instructions for this player'],
        ];

        foreach ($instructions as $r => $row) {
            foreach ($row as $c => $val) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1) . ($r + 3);
                $info->setCellValue($cell, $val);
            }
        }

        $info->getStyle('A3:C3')->getFont()->setBold(true);
        $info->getStyle('A3:C3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9E1F2');

        foreach (['A', 'B', 'C'] as $col) {
            $info->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);
    }
}
