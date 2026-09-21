<?php

namespace App\Http\Controllers;

use App\Models\ColorCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ColorCodeController extends Controller
{
    // ----------------------------------------------------------------
    // Shared validation rules
    // ----------------------------------------------------------------

    private function cmykRules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:100'],
            'cyan'    => ['required', 'integer', 'min:0', 'max:100'],
            'magenta' => ['required', 'integer', 'min:0', 'max:100'],
            'yellow'  => ['required', 'integer', 'min:0', 'max:100'],
            'black'   => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    // ----------------------------------------------------------------
    // Index — both roles
    // ----------------------------------------------------------------

    public function index(): View
    {
        $colors  = ColorCode::with('creator')->orderBy('name')->get();
        $pending = $colors->filter(fn ($c) => $c->is_pending);
        $active  = $colors->filter(fn ($c) => $c->status === 'active');

        return view('color-codes.index', compact('active', 'pending'));
    }

    // ----------------------------------------------------------------
    // Pipeline Manager — create / store
    // ----------------------------------------------------------------

    public function create(): View
    {
        return view('color-codes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->cmykRules());

        ColorCode::create([
            ...$data,
            'status'      => 'active',
            'created_by'  => auth()->id(),
            'approved_by' => auth()->id(),
        ]);

        return redirect()->route('color-codes.index')
            ->with('success', 'Color "' . $data['name'] . '" added.');
    }

    // ----------------------------------------------------------------
    // Pipeline Manager — edit / update
    // ----------------------------------------------------------------

    public function edit(ColorCode $colorCode): View
    {
        return view('color-codes.edit', compact('colorCode'));
    }

    public function update(Request $request, ColorCode $colorCode): RedirectResponse
    {
        $data = $request->validate($this->cmykRules());

        $colorCode->update([
            ...$data,
            'status'          => 'active',
            'approved_by'     => auth()->id(),
            'pending_name'    => null,
            'pending_cyan'    => null,
            'pending_magenta' => null,
            'pending_yellow'  => null,
            'pending_black'   => null,
        ]);

        return redirect()->route('color-codes.index')
            ->with('success', 'Color "' . $colorCode->name . '" updated.');
    }

    // ----------------------------------------------------------------
    // Pipeline Manager — delete
    // ----------------------------------------------------------------

    public function destroy(ColorCode $colorCode): RedirectResponse
    {
        $name = $colorCode->name;
        $colorCode->forceDelete();

        return redirect()->route('color-codes.index')
            ->with('success', 'Color "' . $name . '" deleted.');
    }

    // ----------------------------------------------------------------
    // Pipeline Manager — approve / reject designer requests
    // ----------------------------------------------------------------

    public function approve(ColorCode $colorCode): RedirectResponse
    {
        switch ($colorCode->status) {
            case 'pending_add':
                $colorCode->update([
                    'status'      => 'active',
                    'approved_by' => auth()->id(),
                ]);
                break;

            case 'pending_edit':
                $colorCode->update([
                    'name'            => $colorCode->pending_name    ?? $colorCode->name,
                    'cyan'            => $colorCode->pending_cyan    ?? $colorCode->cyan,
                    'magenta'         => $colorCode->pending_magenta ?? $colorCode->magenta,
                    'yellow'          => $colorCode->pending_yellow  ?? $colorCode->yellow,
                    'black'           => $colorCode->pending_black   ?? $colorCode->black,
                    'status'          => 'active',
                    'approved_by'     => auth()->id(),
                    'pending_name'    => null,
                    'pending_cyan'    => null,
                    'pending_magenta' => null,
                    'pending_yellow'  => null,
                    'pending_black'   => null,
                ]);
                break;

            case 'pending_delete':
                $colorCode->forceDelete();
                break;
        }

        return redirect()->route('color-codes.index')
            ->with('success', 'Request approved.');
    }

    public function reject(ColorCode $colorCode): RedirectResponse
    {
        if ($colorCode->status === 'pending_add') {
            $colorCode->forceDelete();
            return redirect()->route('color-codes.index')
                ->with('success', 'Add request rejected and removed.');
        }

        $colorCode->update([
            'status'          => 'active',
            'pending_name'    => null,
            'pending_cyan'    => null,
            'pending_magenta' => null,
            'pending_yellow'  => null,
            'pending_black'   => null,
        ]);

        return redirect()->route('color-codes.index')
            ->with('success', 'Request rejected. Color restored to active.');
    }

    // ----------------------------------------------------------------
    // Designer — request create / store
    // ----------------------------------------------------------------

    public function requestCreate(): View
    {
        return view('color-codes.request-create');
    }

    public function requestStore(Request $request): RedirectResponse
    {
        $data = $request->validate($this->cmykRules());

        ColorCode::create([
            ...$data,
            'status'     => 'pending_add',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('color-codes.index')
            ->with('success', 'Color submitted for approval.');
    }

    // ----------------------------------------------------------------
    // Designer — request edit / update
    // ----------------------------------------------------------------

    public function requestEdit(ColorCode $colorCode): View
    {
        return view('color-codes.request-edit', compact('colorCode'));
    }

    public function requestUpdate(Request $request, ColorCode $colorCode): RedirectResponse
    {
        if ($colorCode->status !== 'active') {
            return redirect()->route('color-codes.index')
                ->with('error', 'This color already has a pending request.');
        }

        $data = $request->validate($this->cmykRules());

        $colorCode->update([
            'status'          => 'pending_edit',
            'pending_name'    => $data['name'],
            'pending_cyan'    => $data['cyan'],
            'pending_magenta' => $data['magenta'],
            'pending_yellow'  => $data['yellow'],
            'pending_black'   => $data['black'],
        ]);

        return redirect()->route('color-codes.index')
            ->with('success', 'Edit request submitted for approval.');
    }

    // ----------------------------------------------------------------
    // Designer — request delete
    // ----------------------------------------------------------------

    public function requestDestroy(ColorCode $colorCode): RedirectResponse
    {
        if ($colorCode->status !== 'active') {
            return redirect()->route('color-codes.index')
                ->with('error', 'This color already has a pending request.');
        }

        $colorCode->update(['status' => 'pending_delete']);

        return redirect()->route('color-codes.index')
            ->with('success', 'Delete request submitted for approval.');
    }
}
