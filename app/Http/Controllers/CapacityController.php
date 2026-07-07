<?php

namespace App\Http\Controllers;

use App\Models\CapacityConfig;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CapacityController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);

        $configs = CapacityConfig::with('updatedBy')
            ->orderBy('department')
            ->orderBy('product_type')
            ->get()
            ->groupBy('department');

        $productTypes = CapacityConfig::productTypes();

        return view('capacity.index', compact('configs', 'productTypes'));
    }

    public function update(Request $request, SchedulingService $scheduler): RedirectResponse
    {
        abort_unless(auth()->user()->isPipelineManager(), 403);
        $rules = [];
        foreach (['print', 'sew'] as $dept) {
            foreach (array_keys(CapacityConfig::productTypes()) as $type) {
                $rules["rates.{$dept}.{$type}"] = ['required', 'integer', 'min:1', 'max:9999'];
            }
        }

        $data = $request->validate($rules);

        foreach ($data['rates'] as $dept => $types) {
            foreach ($types as $type => $units) {
                CapacityConfig::updateOrCreate(
                    ['department' => $dept, 'product_type' => $type],
                    [
                        'units_per_day' => (int) $units,
                        'updated_by'    => auth()->id(),
                        'updated_at'    => now(),
                    ]
                );
            }
        }

        // Rebuild schedules with new rates
        $scheduler->rebuildSchedules();

        return back()->with('success', 'Capacity settings saved and schedules rebuilt.');
    }
}
