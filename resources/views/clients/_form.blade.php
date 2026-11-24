{{-- Shared client form partial --}}
@php $editing = isset($client) && $client->exists; @endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 fw-semibold">
        <i class="bi bi-person me-2 text-primary"></i>Client Details
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label for="name" class="form-label fw-semibold">
                    Name <span class="text-danger">*</span>
                </label>
                <input type="text" id="name" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $editing ? $client->name : '') }}"
                       placeholder="e.g. FC United Academy"
                       required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 col-md-6">
                <label for="phone" class="form-label fw-semibold">Phone</label>
                <input type="text" id="phone" name="phone"
                       class="form-control @error('phone') is-invalid @enderror"
                       value="{{ old('phone', $editing ? $client->phone : '') }}"
                       placeholder="+60 12-345 6789">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label for="address" class="form-label fw-semibold">Address</label>
                <textarea id="address" name="address" rows="2"
                          class="form-control @error('address') is-invalid @enderror"
                          placeholder="Delivery / billing address (optional)">{{ old('address', $editing ? $client->address : '') }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label for="notes" class="form-label fw-semibold">Notes</label>
                <textarea id="notes" name="notes" rows="2"
                          class="form-control @error('notes') is-invalid @enderror"
                          placeholder="Any extra info about this client (optional)">{{ old('notes', $editing ? $client->notes : '') }}</textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>
