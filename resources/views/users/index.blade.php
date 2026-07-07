@extends('layouts.app')

@section('title', 'Users')
@section('page-title')
    <i class="bi bi-people me-2 text-primary"></i>Users
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="text-muted" style="font-size:.85rem">{{ $users->count() }} user(s) total</div>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i> Add User
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th class="text-center">Status</th>
                    <th class="text-center pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="{{ ! $user->is_active ? 'text-muted' : '' }}">
                        <td class="ps-3 fw-semibold">
                            {{ $user->name }}
                            @if($user->id === auth()->id())
                                <span class="badge bg-primary ms-1" style="font-size:.65rem">You</span>
                            @endif
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-light text-secondary border">
                                {{ $user->role_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($user->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center pe-3">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('users.edit', $user) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($user->id !== auth()->id())
                                    <form method="POST"
                                          action="{{ route('users.toggle-active', $user) }}"
                                          onsubmit="return confirm('{{ $user->is_active ? 'Deactivate' : 'Activate' }} {{ $user->name }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}"
                                                title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="bi bi-{{ $user->is_active ? 'person-slash' : 'person-check' }}"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
