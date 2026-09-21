{{--
    Shared CMYK + HEX form fields with two-way live conversion.
    Optional: $colorCode (model) to pre-fill values.
             $usePending (bool) to pre-fill from pending_* columns.
--}}
@php
    $prefill = isset($colorCode) ? $colorCode : null;
    $usePnd  = $usePending ?? false;
    $name    = old('name',    $prefill ? ($usePnd ? $prefill->pending_name    : $prefill->name)    : '');
    $cyan    = old('cyan',    $prefill ? ($usePnd ? $prefill->pending_cyan    : $prefill->cyan)    : 0);
    $magenta = old('magenta', $prefill ? ($usePnd ? $prefill->pending_magenta : $prefill->magenta) : 0);
    $yellow  = old('yellow',  $prefill ? ($usePnd ? $prefill->pending_yellow  : $prefill->yellow)  : 0);
    $black   = old('black',   $prefill ? ($usePnd ? $prefill->pending_black   : $prefill->black)   : 0);
    // Compute initial hex from CMYK
    $initR   = round(255 * (1 - $cyan/100) * (1 - $black/100));
    $initG   = round(255 * (1 - $magenta/100) * (1 - $black/100));
    $initB   = round(255 * (1 - $yellow/100) * (1 - $black/100));
    $initHex = strtoupper(sprintf('%02x%02x%02x', $initR, $initG, $initB));
@endphp

<div class="row g-4">

    {{-- ── Left: inputs ────────────────────────────────────── --}}
    <div class="col-12 col-md-7">

        {{-- Color name --}}
        <div class="mb-4">
            <label for="cc-name" class="form-label fw-semibold">Color Name</label>
            <input type="text"
                   id="cc-name"
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

        {{-- HEX input --}}
        <div class="mb-3">
            <label for="cc-hex" class="form-label fw-semibold d-flex align-items-center gap-2">
                HEX
                <span class="text-muted fw-normal" style="font-size:.8rem">— enter hex to auto-fill CMYK</span>
            </label>
            <div class="input-group" style="max-width:220px">
                <span class="input-group-text">#</span>
                <input type="text"
                       id="cc-hex"
                       class="form-control text-uppercase"
                       style="font-family:monospace;letter-spacing:1px"
                       maxlength="6"
                       placeholder="e.g. FF5733"
                       value="{{ $initHex }}">
            </div>
            <div id="cc-hex-error" class="text-danger mt-1" style="font-size:.8rem;display:none">
                Invalid hex — enter 6 characters (0-9, A-F)
            </div>
        </div>

        {{-- CMYK --}}
        <div class="mb-2">
            <label class="form-label fw-semibold">
                CMYK
                <span class="text-muted fw-normal" style="font-size:.8rem">— or adjust sliders to auto-fill HEX</span>
            </label>
        </div>

        <div class="row g-3">

            {{-- C --}}
            <div class="col-12 col-sm-6">
                <label for="cc-cyan" class="form-label small d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-info fw-bold" style="font-size:.75rem;min-width:22px">C</span>Cyan
                </label>
                <div class="d-flex align-items-center gap-2">
                    <input type="range" class="form-range flex-grow-1" id="cc-cyan-range"
                           min="0" max="100" value="{{ $cyan }}"
                           oninput="cc_rangeToNum('cyan');cc_cmykToHex()">
                    <input type="number" id="cc-cyan" name="cyan"
                           class="form-control form-control-sm text-center @error('cyan') is-invalid @enderror"
                           style="width:65px" min="0" max="100" value="{{ $cyan }}" required
                           oninput="cc_numToRange('cyan');cc_cmykToHex()">
                </div>
                @error('cyan')<div class="text-danger" style="font-size:.8rem">{{ $message }}</div>@enderror
            </div>

            {{-- M --}}
            <div class="col-12 col-sm-6">
                <label for="cc-magenta" class="form-label small d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-danger fw-bold" style="font-size:.75rem;min-width:22px">M</span>Magenta
                </label>
                <div class="d-flex align-items-center gap-2">
                    <input type="range" class="form-range flex-grow-1" id="cc-magenta-range"
                           min="0" max="100" value="{{ $magenta }}"
                           oninput="cc_rangeToNum('magenta');cc_cmykToHex()">
                    <input type="number" id="cc-magenta" name="magenta"
                           class="form-control form-control-sm text-center @error('magenta') is-invalid @enderror"
                           style="width:65px" min="0" max="100" value="{{ $magenta }}" required
                           oninput="cc_numToRange('magenta');cc_cmykToHex()">
                </div>
                @error('magenta')<div class="text-danger" style="font-size:.8rem">{{ $message }}</div>@enderror
            </div>

            {{-- Y --}}
            <div class="col-12 col-sm-6">
                <label for="cc-yellow" class="form-label small d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-warning text-dark fw-bold" style="font-size:.75rem;min-width:22px">Y</span>Yellow
                </label>
                <div class="d-flex align-items-center gap-2">
                    <input type="range" class="form-range flex-grow-1" id="cc-yellow-range"
                           min="0" max="100" value="{{ $yellow }}"
                           oninput="cc_rangeToNum('yellow');cc_cmykToHex()">
                    <input type="number" id="cc-yellow" name="yellow"
                           class="form-control form-control-sm text-center @error('yellow') is-invalid @enderror"
                           style="width:65px" min="0" max="100" value="{{ $yellow }}" required
                           oninput="cc_numToRange('yellow');cc_cmykToHex()">
                </div>
                @error('yellow')<div class="text-danger" style="font-size:.8rem">{{ $message }}</div>@enderror
            </div>

            {{-- K --}}
            <div class="col-12 col-sm-6">
                <label for="cc-black" class="form-label small d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-dark fw-bold" style="font-size:.75rem;min-width:22px">K</span>Black
                </label>
                <div class="d-flex align-items-center gap-2">
                    <input type="range" class="form-range flex-grow-1" id="cc-black-range"
                           min="0" max="100" value="{{ $black }}"
                           oninput="cc_rangeToNum('black');cc_cmykToHex()">
                    <input type="number" id="cc-black" name="black"
                           class="form-control form-control-sm text-center @error('black') is-invalid @enderror"
                           style="width:65px" min="0" max="100" value="{{ $black }}" required
                           oninput="cc_numToRange('black');cc_cmykToHex()">
                </div>
                @error('black')<div class="text-danger" style="font-size:.8rem">{{ $message }}</div>@enderror
            </div>

        </div>
    </div>

    {{-- ── Right: live preview ──────────────────────────────── --}}
    <div class="col-12 col-md-5 d-flex flex-column align-items-center justify-content-center pt-2">
        <div class="text-muted fw-semibold mb-2" style="font-size:.85rem">Live Preview</div>
        <div id="cc-preview-swatch"
             style="width:130px;height:90px;border-radius:12px;border:1px solid rgba(0,0,0,.12);
                    box-shadow:0 2px 10px rgba(0,0,0,.13);transition:background .08s;
                    background:#{{ $initHex }}">
        </div>
        <div id="cc-preview-hex"
             class="mt-2 fw-semibold"
             style="font-size:.85rem;font-family:monospace;letter-spacing:1px">
            #{{ $initHex }}
        </div>
    </div>

</div>

<script>
(function () {
    // ── Helpers ──────────────────────────────────────────────
    function val(id)   { return parseInt(document.getElementById(id).value) || 0; }
    function setVal(id, v) { var el = document.getElementById(id); if (el) el.value = v; }

    function cmykToRgb(c, m, y, k) {
        return {
            r: Math.round(255 * (1 - c/100) * (1 - k/100)),
            g: Math.round(255 * (1 - m/100) * (1 - k/100)),
            b: Math.round(255 * (1 - y/100) * (1 - k/100))
        };
    }

    function rgbToCmyk(r, g, b) {
        r /= 255; g /= 255; b /= 255;
        var k = 1 - Math.max(r, g, b);
        if (k === 1) return { c:0, m:0, y:0, k:100 };
        var c = (1 - r - k) / (1 - k);
        var m = (1 - g - k) / (1 - k);
        var y = (1 - b - k) / (1 - k);
        return {
            c: Math.round(c * 100),
            m: Math.round(m * 100),
            y: Math.round(y * 100),
            k: Math.round(k * 100)
        };
    }

    function toHex(n) { return n.toString(16).padStart(2, '0'); }

    function updateSwatch(hex) {
        var swatch = document.getElementById('cc-preview-swatch');
        var label  = document.getElementById('cc-preview-hex');
        if (swatch) swatch.style.background = '#' + hex;
        if (label)  label.textContent = '#' + hex.toUpperCase();
    }

    // ── CMYK → HEX (called when any CMYK input changes) ─────
    window.cc_cmykToHex = function () {
        var rgb = cmykToRgb(
            val('cc-cyan'), val('cc-magenta'),
            val('cc-yellow'), val('cc-black')
        );
        var hex = toHex(rgb.r) + toHex(rgb.g) + toHex(rgb.b);
        setVal('cc-hex', hex.toUpperCase());
        updateSwatch(hex);
        document.getElementById('cc-hex-error').style.display = 'none';
    };

    // ── HEX → CMYK (called when hex input changes) ───────────
    document.getElementById('cc-hex').addEventListener('input', function () {
        var raw = this.value.replace(/[^0-9a-fA-F]/g, '');
        this.value = raw.toUpperCase();

        if (raw.length !== 6) {
            document.getElementById('cc-hex-error').style.display = raw.length > 0 ? '' : 'none';
            return;
        }
        document.getElementById('cc-hex-error').style.display = 'none';

        var r = parseInt(raw.slice(0,2), 16);
        var g = parseInt(raw.slice(2,4), 16);
        var b = parseInt(raw.slice(4,6), 16);
        var cmyk = rgbToCmyk(r, g, b);

        ['cyan','magenta','yellow','black'].forEach(function(ch) {
            setVal('cc-' + ch,        cmyk[ch === 'cyan' ? 'c' : ch === 'magenta' ? 'm' : ch === 'yellow' ? 'y' : 'k']);
            setVal('cc-' + ch + '-range', cmyk[ch === 'cyan' ? 'c' : ch === 'magenta' ? 'm' : ch === 'yellow' ? 'y' : 'k']);
        });

        updateSwatch(raw);
    });

    // ── Sync range → number field ────────────────────────────
    window.cc_rangeToNum = function (ch) {
        var v = document.getElementById('cc-' + ch + '-range').value;
        setVal('cc-' + ch, v);
    };

    // ── Sync number → range ──────────────────────────────────
    window.cc_numToRange = function (ch) {
        var v = document.getElementById('cc-' + ch).value;
        setVal('cc-' + ch + '-range', v);
    };

    // Run once on load to initialise swatch to existing values
    window.cc_cmykToHex();
})();
</script>
