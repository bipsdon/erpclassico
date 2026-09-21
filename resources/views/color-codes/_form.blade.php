{{--
    Reusable CMYK form fields.
    $prefix — unique string prepended to each input id to avoid id collisions
              across multiple modals on the same page.
              e.g. '' → id="add-name", 'edit-' → id="edit-name"
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

{{-- CMYK sliders + number inputs --}}
<div class="mb-3">
    <label class="form-label fw-semibold">CMYK Values <span class="text-muted fw-normal">(0 – 100)</span></label>
    <div class="row g-2">

        @foreach([
            ['C', 'cyan',    'info',    '#0dcaf0'],
            ['M', 'magenta', 'danger',  '#dc3545'],
            ['Y', 'warning', 'warning', '#ffc107'],
            ['K', 'black',   'dark',    '#212529'],
        ] as [$label, $field, $color, $accent])
        <div class="col-6">
            <label for="{{ $p }}{{ $field }}" class="form-label small mb-1 d-flex align-items-center gap-1">
                <span class="badge bg-{{ $color }} {{ $color === 'warning' ? 'text-dark' : '' }}"
                      style="font-size:.7rem;min-width:18px">{{ $label }}</span>
                <span class="text-muted">{{ ucfirst($field) }}</span>
            </label>
            <div class="d-flex align-items-center gap-2">
                <input type="range"
                       class="form-range flex-grow-1"
                       min="0" max="100"
                       id="{{ $p }}{{ $field }}-range"
                       oninput="document.getElementById('{{ $p }}{{ $field }}').value=this.value;document.getElementById('{{ $p }}{{ $field }}').dispatchEvent(new Event('input'))">
                <input type="number"
                       id="{{ $p }}{{ $field }}"
                       name="{{ $field }}"
                       class="form-control form-control-sm text-center"
                       style="width:60px"
                       min="0" max="100"
                       value="0"
                       required
                       oninput="document.getElementById('{{ $p }}{{ $field }}-range').value=this.value">
            </div>
        </div>
        @endforeach

    </div>
</div>

{{-- Live preview --}}
<div class="d-flex align-items-center gap-3 mt-3 p-3 bg-light rounded">
    <div id="{{ $p }}preview-swatch"
         style="width:56px;height:38px;border-radius:6px;border:1px solid rgba(0,0,0,.2);background:#000;transition:background .15s">
    </div>
    <div>
        <div class="fw-semibold" style="font-size:.85rem">Live Preview</div>
        <div class="text-muted" style="font-size:.75rem">Updates as you adjust the sliders</div>
    </div>
</div>
