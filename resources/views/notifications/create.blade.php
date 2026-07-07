@extends('layouts.app')

@section('title', 'Send Notification')
@section('page-title')
    <i class="bi bi-megaphone me-2 text-primary"></i>Send Notification
@endsection

@section('content')

<div class="row g-4">

    {{-- Send form --}}
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 fw-semibold">
                <i class="bi bi-send me-2 text-primary"></i>New Notification
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('notifications.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Send To <span class="text-danger">*</span></label>
                        <select name="target_department"
                                class="form-select @error('target_department') is-invalid @enderror">
                            <option value="">📢 All Departments (Broadcast)</option>
                            <option value="designer"         {{ old('target_department') === 'designer'         ? 'selected' : '' }}>
                                🎨 Design Team
                            </option>
                            <option value="printing_manager" {{ old('target_department') === 'printing_manager' ? 'selected' : '' }}>
                                🖨️ Printing Team
                            </option>
                            <option value="sewing_manager"   {{ old('target_department') === 'sewing_manager'   ? 'selected' : '' }}>
                                ✂️ Sewing Team
                            </option>
                        </select>
                        @error('target_department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                        <input type="text"
                               name="subject"
                               class="form-control @error('subject') is-invalid @enderror"
                               value="{{ old('subject') }}"
                               placeholder="e.g. Urgent: Priority order change"
                               required>
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                        <textarea name="message"
                                  rows="5"
                                  class="form-control @error('message') is-invalid @enderror"
                                  placeholder="Write your message here…"
                                  required>{{ old('message') }}</textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send me-1"></i> Send Notification
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Sent history --}}
    <div class="col-12 col-lg-7">
        <div class="section-title">
            <i class="bi bi-clock-history me-2"></i>Recently Sent
        </div>
        <div class="card shadow-sm border-0">
            @if($sent->isEmpty())
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                    No notifications sent yet.
                </div>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($sent as $n)
                        <li class="list-group-item px-3 py-3">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-{{ $n->isBroadcast() ? 'primary' : 'secondary' }}">
                                            {{ $n->target_label }}
                                        </span>
                                        <span class="fw-semibold text-truncate" style="font-size:.875rem">
                                            {{ $n->subject }}
                                        </span>
                                    </div>
                                    <p class="text-muted mb-1" style="font-size:.8rem;white-space:pre-line">
                                        {{ Str::limit($n->message, 120) }}
                                    </p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>{{ $n->created_at->diffForHumans() }}
                                        · {{ $n->reads->count() }} read(s)
                                    </small>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

</div>

@endsection
