@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title')
    <i class="bi bi-pencil me-2 text-primary"></i>Edit User
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                            <option value="pipeline_manager" {{ old('role', $user->role) === 'pipeline_manager' ? 'selected' : '' }}>Pipeline Manager</option>
                            <option value="designer"         {{ old('role', $user->role) === 'designer'         ? 'selected' : '' }}>Designer</option>
                            <option value="printing_manager" {{ old('role', $user->role) === 'printing_manager' ? 'selected' : '' }}>Printing Manager</option>
                            <option value="sewing_manager"   {{ old('role', $user->role) === 'sewing_manager'   ? 'selected' : '' }}>Sewing Manager</option>
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <hr>
                    <p class="text-muted small mb-3">Leave password fields blank to keep the existing password.</p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2 me-1"></i> Save Changes
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
