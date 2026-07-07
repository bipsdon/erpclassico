{{--
    Shared order form partial.
    $order may be null (create) or an existing Order (edit).
--}}
@php
    $editing = isset($order) && $order->exists;
    $old     = function(string $field, mixed $default = '') use ($editing, $order) {
        if (old($field) !== null) {
            return old($field);
        }
        if ($editing) {
            $value = $order->{$field};
            // Carbon date objects must be formatted as Y-m-d for <input type="date">
            if ($value instanceof \Illuminate\Support\Carbon) {
                return $value->format('Y-m-d');
            }
            return $value;
        }
        return $default;
    };
@endphp

{{-- ── Section: Customer Info ─────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-person me-2 text-primary"></i>Customer Information
    </div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-12 col-md-6">
                <label for="customer_name" class="form-label fw-semibold">
                    Customer Name <span class="text-danger">*</span>
                </label>
                <input type="text"
                       id="customer_name"
                       name="customer_name"
                       class="form-control @error('customer_name') is-invalid @enderror"
                       value="{{ $old('customer_name') }}"
                       placeholder="e.g. FC United Academy"
                       required>
                @error('customer_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="customer_phone" class="form-label fw-semibold">
                    Phone Number <span class="text-danger">*</span>
                </label>
                <input type="text"
                       id="customer_phone"
                       name="customer_phone"
                       class="form-control @error('customer_phone') is-invalid @enderror"
                       value="{{ $old('customer_phone') }}"
                       placeholder="+60 12-345 6789"
                       required>
                @error('customer_phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="whatsapp_order_id" class="form-label fw-semibold">
                    <i class="bi bi-whatsapp text-success me-1"></i>WhatsApp Order ID
                    <small class="text-muted fw-normal">(optional)</small>
                </label>
                <input type="text"
                       id="whatsapp_order_id"
                       name="whatsapp_order_id"
                       class="form-control @error('whatsapp_order_id') is-invalid @enderror"
                       value="{{ $old('whatsapp_order_id') }}"
                       placeholder="e.g. WA-20240601-001">
                @error('whatsapp_order_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </div>
</div>

{{-- ── Section: Order Details ──────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-clipboard me-2 text-primary"></i>Order Details
    </div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-6 col-md-3">
                <label for="quantity" class="form-label fw-semibold">
                    Quantity <span class="text-danger">*</span>
                </label>
                <input type="number"
                       id="quantity"
                       name="quantity"
                       class="form-control @error('quantity') is-invalid @enderror"
                       value="{{ $old('quantity', '') }}"
                       min="1"
                       max="9999"
                       placeholder="22"
                       required>
                @error('quantity')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-6 col-md-3">
                <label for="product_type" class="form-label fw-semibold">
                    Product Type <span class="text-danger">*</span>
                </label>
                <select id="product_type"
                        name="product_type"
                        class="form-select @error('product_type') is-invalid @enderror"
                        required>
                    @foreach(\App\Models\CapacityConfig::productTypes() as $value => $label)
                        <option value="{{ $value }}"
                                {{ $old('product_type', 'jersey') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('product_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-6 col-md-3">
                <label for="priority" class="form-label fw-semibold">
                    Priority <span class="text-danger">*</span>
                </label>
                <select id="priority"
                        name="priority"
                        class="form-select @error('priority') is-invalid @enderror"
                        required>
                    <option value="normal"   {{ $old('priority', 'normal') === 'normal'   ? 'selected' : '' }}>Normal</option>
                    <option value="rush"     {{ $old('priority', 'normal') === 'rush'     ? 'selected' : '' }}>Rush</option>
                    <option value="critical" {{ $old('priority', 'normal') === 'critical' ? 'selected' : '' }}>Critical</option>
                </select>
                @error('priority')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-6 col-md-3">
                <label for="order_date" class="form-label fw-semibold">
                    Order Date <span class="text-danger">*</span>
                </label>
                <input type="date"
                       id="order_date"
                       name="order_date"
                       class="form-control @error('order_date') is-invalid @enderror"
                       value="{{ $old('order_date', $editing ? '' : now()->toDateString()) }}"
                       required>
                @error('order_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-6 col-md-3">
                <label for="delivery_date" class="form-label fw-semibold">
                    Delivery Date <span class="text-danger">*</span>
                </label>
                <input type="date"
                       id="delivery_date"
                       name="delivery_date"
                       class="form-control @error('delivery_date') is-invalid @enderror"
                       value="{{ $old('delivery_date', '') }}"
                       required>
                @error('delivery_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Stage + Status (edit only) --}}
            @if($editing)
                <div class="col-6 col-md-3">
                    <label for="stage" class="form-label fw-semibold">Stage</label>
                    <select id="stage" name="stage" class="form-select">
                        @foreach(['design' => 'Design', 'print' => 'Print', 'sew' => 'Sewing', 'ready' => 'Ready', 'delivered' => 'Delivered'] as $val => $label)
                            <option value="{{ $val }}" {{ $old('stage') === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label for="status" class="form-label fw-semibold">Status</label>
                    <select id="status" name="status" class="form-select">
                        @foreach(['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'on_hold' => 'On Hold', 'cancelled' => 'Cancelled'] as $val => $label)
                            <option value="{{ $val }}" {{ $old('status') === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

        </div>
    </div>
</div>

{{-- ── Section: Specifications (Rich Text) ───────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-file-richtext me-2 text-primary"></i>Order Specifications
        <small class="text-muted fw-normal ms-2">Jersey details, fabric, colours, sponsor requirements…</small>
    </div>
    <div class="card-body pb-2">
        {{-- Quill toolbar target --}}
        <div id="quill-toolbar">
            <span class="ql-formats">
                <select class="ql-header">
                    <option selected></option>
                    <option value="1"></option>
                    <option value="2"></option>
                </select>
            </span>
            <span class="ql-formats">
                <button class="ql-bold"></button>
                <button class="ql-italic"></button>
                <button class="ql-underline"></button>
            </span>
            <span class="ql-formats">
                <button class="ql-list" value="ordered"></button>
                <button class="ql-list" value="bullet"></button>
            </span>
            <span class="ql-formats">
                <button class="ql-link"></button>
                <button class="ql-image"></button>
                <button class="ql-clean"></button>
            </span>
        </div>
        <div id="quill-editor"
             style="min-height:180px;font-size:.9rem">{!! old('details', $editing ? $order->details : '') !!}</div>
        <input type="hidden" name="details" id="details-input">
    </div>
</div>

{{-- ── Section: Internal Notes ────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-sticky me-2 text-warning"></i>Internal Notes
        <small class="text-muted fw-normal ms-2">Visible to pipeline manager only</small>
    </div>
    <div class="card-body">
        <textarea id="notes"
                  name="notes"
                  class="form-control @error('notes') is-invalid @enderror"
                  rows="3"
                  placeholder="Optional internal notes…">{{ $old('notes', '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

{{-- ── Section: Name & Number List ────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <span class="fw-semibold">
            <i class="bi bi-people me-2 text-primary"></i>Name & Number List
            <small class="text-muted fw-normal ms-1">(optional)</small>
        </span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-player-btn">
            <i class="bi bi-plus-lg me-1"></i>Add Player
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle" id="players-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:2rem">#</th>
                        <th>Player Name</th>
                        <th style="width:100px">Jersey #</th>
                        <th style="width:90px">Size</th>
                        <th>Notes</th>
                        <th style="width:48px"></th>
                    </tr>
                </thead>
                <tbody id="players-body">
                    @php
                        $existingPlayers = old('players', $editing ? $order->players->toArray() : []);
                    @endphp

                    @forelse($existingPlayers as $i => $player)
                        <tr class="player-row">
                            <td class="ps-3 text-muted row-num">{{ $i + 1 }}</td>
                            <input type="hidden" name="players[{{ $i }}][id]"
                                   value="{{ $player['id'] ?? '' }}">
                            <td>
                                <input type="text"
                                       name="players[{{ $i }}][player_name]"
                                       class="form-control form-control-sm"
                                       value="{{ $player['player_name'] ?? '' }}"
                                       placeholder="Player name"
                                       required>
                            </td>
                            <td>
                                <input type="text"
                                       name="players[{{ $i }}][jersey_number]"
                                       class="form-control form-control-sm text-center"
                                       value="{{ $player['jersey_number'] ?? '' }}"
                                       placeholder="10">
                            </td>
                            <td>
                                <select name="players[{{ $i }}][size]"
                                        class="form-select form-select-sm">
                                    <option value="">—</option>
                                    @foreach(['XS','S','M','L','XL','XXL','3XL'] as $sz)
                                        <option value="{{ $sz }}"
                                                {{ ($player['size'] ?? '') === $sz ? 'selected' : '' }}>
                                            {{ $sz }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text"
                                       name="players[{{ $i }}][notes]"
                                       class="form-control form-control-sm"
                                       value="{{ $player['notes'] ?? '' }}"
                                       placeholder="Special instructions">
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger remove-player-btn"
                                        title="Remove">
                                    <i class="bi bi-x"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="no-players-row">
                            <td colspan="6" class="text-center text-muted py-3" style="font-size:.85rem">
                                No players added yet. Click "Add Player" to begin.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Section: Attachments ───────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-paperclip me-2 text-primary"></i>Guidelines & Attachments
        <small class="text-muted fw-normal ms-1">PDF, JPG, PNG, AI, EPS, ZIP · max 20 MB each</small>
    </div>
    <div class="card-body">

        {{-- Existing attachments (edit mode) --}}
        @if($editing && $order->attachments->isNotEmpty())
            <div class="mb-3">
                <div class="text-muted small fw-semibold mb-2">Existing files:</div>
                @foreach($order->attachments as $attachment)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi {{ $attachment->file_icon }} text-secondary"></i>
                        <span style="font-size:.85rem">{{ $attachment->original_name }}</span>
                        <span class="text-muted" style="font-size:.75rem">({{ $attachment->file_size_human }})</span>
                        <form method="POST"
                              action="{{ route('orders.attachments.delete', [$order, $attachment]) }}"
                              class="ms-auto"
                              onsubmit="return confirm('Remove this file?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger py-0">
                                <i class="bi bi-x"></i>
                            </button>
                        </form>
                    </div>
                @endforeach
                <hr>
            </div>
        @endif

        <input type="file"
               id="attachments"
               name="attachments[]"
               class="form-control @error('attachments.*') is-invalid @enderror"
               multiple
               accept=".pdf,.jpg,.jpeg,.png,.ai,.eps,.zip">
        @error('attachments.*')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
        <div class="text-muted small mt-2">
            <i class="bi bi-info-circle me-1"></i>
            Hold <kbd>Ctrl</kbd> or <kbd>⌘</kbd> to select multiple files.
        </div>
    </div>
</div>
