{{--
    Shared CMYK form fields.
    Optional variables:
      $colorCode  — existing ColorCode model (for edit/request-edit pages, pre-fills values)
      $usePending — true to pre-fill from pending_* columns instead of live columns
--}}
@php
    $prefill = isset($colorCode) ? $colorCode : null;
    $name    = old('name',    $prefill ? ($usePending ?? false ? $prefill->pending_name    : $prefill->name)    : '');
    $cyan    = old('cyan',    $prefill ? ($usePending ?? false ? $prefill->pending_cyan    : $prefill->cyan)    : 0);
    $magenta = old('magenta', $prefill ? ($usePending ?? false ? $prefill->pending_magenta : $prefill->magenta) : 0);
    $yellow  = old('yellow',  $prefill ? ($usePending ?? false ? $prefill->pending_yellow  : $prefill->yellow)  : 0);
    $black   = old('black',   $prefill ? ($usePending ?? false ? $prefill->pending_black   : $prefill->black)   : 0);
@endphp

<div class="row g-4">

    {{-- Left: inputs --}}
    <div class="col-12 col-md-7">

        {{-- Color name --}}
        <div class="mb-4">
            <label for="name" class="form-label fw-semibold">Color Name</label>
            <input type="text"
                   id="name"
                   name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ $name }}"
                   placeholder="e.g. Royal Blue, Maroon"
                   maxlength="100"
                   required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- CMYK --}}
        <div class="mb-2">
            <label class="form-label fw-semibold">CMYK Values <span class="text-muted fw-normal">(0 – 100)</span></label>
        </div>

        <div class="row g-3">

            {{-- C --}}
            <div class="col-12 col-sm-6">
                <label for="cyan" class="form-label small d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-info fw-bold" style="font-size:.75rem;min-width:22px">C</span>
                    Cyan
                </label>
                <div class="d-flex align-items-center gap-2">
                    <input type="range" class="form-range flex-grow-1" id="cyan-range"
                           min="0" max="100" value="{{ $cyan }}"
                           oninput="document.getElementById('cyan').value=this.value;livePreview()">
                    <input type="number" id="cyan" name="cyan"
                           class="form-control form-control-sm text-center @error('cyan') is-invalid @enderror"
                           style="width:65px" min="0" max="100" value="{{ $cyan }}" required
                           oninput="document.getElementById('cyan-range').value=this.value;livePreview()">
                </div>
                @error('cyan')<div class="text-danger" style="font-size:.8rem">{{ $message }}</div>@enderror
            </div>

            {{-- M --}}
            <div class="col-12 col-sm-6">
                <label for="magenta" class="form-label small d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-danger fw-bold" style="font-size:.75rem;min-width:22px">M</span>
                    Magenta
                </label>
                <div class="d-flex align-items-center gap-2">
                    <input type="range" class="form-range flex-grow-1" id="magenta-range"
                           min="0" max="100" value="{{ $magenta }}"
                           oninput="document.getElementById('magenta').value=this.value;livePreview()">
                    <input type="number" id="magenta" name="magenta"
                           class="form-control form-control-sm text-center @error('magenta') is-invalid @enderror"
                           style="width:65px" min="0" max="100" value="{{ $magenta }}" required
                           oninput="document.getElementById('magenta-range').value=this.value;livePreview()">
                </div>
                @error('magenta')<div class="text-danger" style="font-size:.8rem">{{ $message }}</div>@enderror
            </div>

            {{-- Y --}}
            <div class="col-12 col-sm-6">
                <label for="yellow" class="form-label small d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-warning text-dark fw-bold" style="font-size:.75rem;min-width:22px">Y</span>
                    Yellow
                </label>
                <div class="d-flex align-items-center gap-2">
                    <input type="range" class="form-range flex-grow-1" id="yellow-range"
                           min="0" max="100" value="{{ $yellow }}"
                           oninput="document.getElementById('yellow').value=this.value;livePreview()">
                    <input type="number" id="yellow" name="yellow"
                           class="form-control form-control-sm text-center @error('yellow') is-invalid @enderror"
                           style="width:65px" min="0" max="100" value="{{ $yellow }}" required
                           oninput="document.getElementById('yellow-range').value=this.value;livePreview()">
                </div>
                @error('yellow')<div class="text-danger" style="font-size:.8rem">{{ $message }}</div>@enderror
            </div>

            {{-- K --}}
            <div class="col-12 col-sm-6">
                <label for="black" class="form-label small d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-dark fw-bold" style="font-size:.75rem;min-width:22px">K</span>
                    Black
                </label>
                <div class="d-flex align-items-center gap-2">
                    <input type="range" class="form-range flex-grow-1" id="black-range"
                           min="0" max="100" value="{{ $black }}"
                           oninput="document.getElementById('black').value=this.value;livePreview()">
                    <input type="number" id="black" name="black"
                           class="form-control form-control-sm text-center @error('black') is-invalid @enderror"
                           style="width:65px" min="0" max="100" value="{{ $black }}" required
                           oninput="document.getElementById('black-range').value=this.value;livePreview()">
                </div>
                @error('black')<div class="text-danger" style="font-size:.8rem">{{ $message }}</div>@enderror
            </div>

        </div>
    </div>

    {{-- Right: live preview --}}
    <div class="col-12 col-md-5 d-flex flex-column align-items-center justify-content-center">
        <div class="text-muted fw-semibold mb-2" style="font-size:.85rem">Live Preview</div>
        <div id="color-preview-swatch"
             style="width:120px;height:80px;border-radius:10px;border:1px solid rgba(0,0,0,.15);box-shadow:0 2px 8px rgba(0,0,0,.12);transition:background .1s">
        </div>
        <div id="color-preview-hex" class="text-muted mt-2" style="font-size:.78rem;font-family:monospace"></div>
    </div>

</div>

<script>
function livePreview() {
    var c = parseInt(document.getElementById('cyan').value)    || 0;
    var m = parseInt(document.getElementById('magenta').value) || 0;
    var y = parseInt(document.getElementById('yellow').value)  || 0;
    var k = parseInt(document.getElementById('black').value)   || 0;

    var r = Math.round(255 * (1 - c/100) * (1 - k/100));
    var g = Math.round(255 * (1 - m/100) * (1 - k/100));
    var b = Math.round(255 * (1 - y/100) * (1 - k/100));

    var hex = '#' + [r,g,b].map(function(v){
        return v.toString(16).padStart(2,'0');
    }).join('');

    document.getElementById('color-preview-swatch').style.background = hex;
    document.getElementById('color-preview-hex').textContent = hex.toUpperCase();
}

// Run once on page load to show initial color
livePreview();
</script>
