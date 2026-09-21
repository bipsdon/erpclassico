{{--
    Reusable CMYK form fields.
    $prefix — unique string prepended to each input id to avoid id collisions
              across multiple modals on the same page.
              e.g. ''         → id="name",         swatch id="preview-swatch"
                   'edit-'    → id="edit-name",    swatch id="edit-preview-swatch"
                   'req-'     → id="req-name",     swatch id="req-preview-swatch"
                   'req-edit-'→ id="req-edit-name", swatch id="req-edit-preview-swatch"
--}}
@php $p = $prefix ?? ''; @endphp

{{-- Color name --}}
<div class="mb-3">
    <label for="{{ $p }}name" class="form-label fw-semibold">Color Name</label>
    <input type="text"
           id="{{ $p }}name"
           name="name"
           class="form-control"
           placeholder="e.g. Royal Blue, Maroon"
           maxlength="100"
           required>
</div>

{{-- CMYK inputs --}}
<div class="mb-3">
    <label class="form-label fw-semibold">CMYK Values <span class="text-muted fw-normal">(0 – 100)</span></label>
    <div class="row g-2">

        {{-- Cyan --}}
        <div class="col-6">
            <label for="{{ $p }}cyan" class="form-label small mb-1 d-flex align-items-center gap-1">
                <span class="badge bg-info" style="font-size:.7rem;min-width:18px">C</span>
                <span class="text-muted">Cyan</span>
            </label>
            <div class="d-flex align-items-center gap-2">
                <input type="range" class="form-range flex-grow-1" min="0" max="100"
                       id="{{ $p }}cyan-range"
                       oninput="syncCmyk('{{ $p }}cyan')">
                <input type="number" id="{{ $p }}cyan" name="cyan"
                       class="form-control form-control-sm text-center" style="width:60px"
                       min="0" max="100" value="0" required
                       oninput="syncCmykRange('{{ $p }}cyan')">
            </div>
        </div>

        {{-- Magenta --}}
        <div class="col-6">
            <label for="{{ $p }}magenta" class="form-label small mb-1 d-flex align-items-center gap-1">
                <span class="badge bg-danger" style="font-size:.7rem;min-width:18px">M</span>
                <span class="text-muted">Magenta</span>
            </label>
            <div class="d-flex align-items-center gap-2">
                <input type="range" class="form-range flex-grow-1" min="0" max="100"
                       id="{{ $p }}magenta-range"
                       oninput="syncCmyk('{{ $p }}magenta')">
                <input type="number" id="{{ $p }}magenta" name="magenta"
                       class="form-control form-control-sm text-center" style="width:60px"
                       min="0" max="100" value="0" required
                       oninput="syncCmykRange('{{ $p }}magenta')">
            </div>
        </div>

        {{-- Yellow --}}
        <div class="col-6">
            <label for="{{ $p }}yellow" class="form-label small mb-1 d-flex align-items-center gap-1">
                <span class="badge bg-warning text-dark" style="font-size:.7rem;min-width:18px">Y</span>
                <span class="text-muted">Yellow</span>
            </label>
            <div class="d-flex align-items-center gap-2">
                <input type="range" class="form-range flex-grow-1" min="0" max="100"
                       id="{{ $p }}yellow-range"
                       oninput="syncCmyk('{{ $p }}yellow')">
                <input type="number" id="{{ $p }}yellow" name="yellow"
                       class="form-control form-control-sm text-center" style="width:60px"
                       min="0" max="100" value="0" required
                       oninput="syncCmykRange('{{ $p }}yellow')">
            </div>
        </div>

        {{-- Black --}}
        <div class="col-6">
            <label for="{{ $p }}black" class="form-label small mb-1 d-flex align-items-center gap-1">
                <span class="badge bg-dark" style="font-size:.7rem;min-width:18px">K</span>
                <span class="text-muted">Black</span>
            </label>
            <div class="d-flex align-items-center gap-2">
                <input type="range" class="form-range flex-grow-1" min="0" max="100"
                       id="{{ $p }}black-range"
                       oninput="syncCmyk('{{ $p }}black')">
                <input type="number" id="{{ $p }}black" name="black"
                       class="form-control form-control-sm text-center" style="width:60px"
                       min="0" max="100" value="0" required
                       oninput="syncCmykRange('{{ $p }}black')">
            </div>
        </div>

    </div>
</div>

{{-- Live preview swatch --}}
<div class="d-flex align-items-center gap-3 mt-3 p-3 bg-light rounded">
    <div id="{{ $p }}preview-swatch"
         style="width:56px;height:38px;border-radius:6px;border:1px solid rgba(0,0,0,.2);background:#000;flex-shrink:0;transition:background .15s">
    </div>
    <div>
        <div class="fw-semibold" style="font-size:.85rem">Live Preview</div>
        <div class="text-muted" style="font-size:.75rem">Updates as you adjust the sliders</div>
    </div>
</div>
