<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ERP Classico') — ERP Classico</title>

    {{-- Bootstrap 5 CDN --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    {{-- Bootstrap Icons CDN --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --erp-sidebar-width: 240px;
            --erp-topbar-height: 56px;
            --erp-brand-color: #1a3c5e;
            --erp-brand-hover: #14304e;
        }

        /* ── Sidebar ─────────────────────────────── */
        #sidebar {
            width: var(--erp-sidebar-width);
            min-height: 100vh;
            background: var(--erp-brand-color);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1030;
            transition: transform .25s ease;
        }

        #sidebar .sidebar-brand {
            height: var(--erp-topbar-height);
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.12);
            font-weight: 700;
            font-size: 1.05rem;
            color: #fff;
            text-decoration: none;
            letter-spacing: .5px;
        }

        #sidebar .nav-link {
            color: rgba(255,255,255,.75);
            padding: .55rem 1.25rem;
            font-size: .875rem;
            border-radius: 0;
            transition: background .15s, color .15s;
        }

        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            background: rgba(255,255,255,.1);
            color: #fff;
        }

        #sidebar .nav-link i {
            width: 1.25rem;
            text-align: center;
            margin-right: .5rem;
        }

        #sidebar .nav-section {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,.4);
            padding: 1rem 1.25rem .25rem;
        }

        /* ── Main content area ───────────────────── */
        #main-content {
            margin-left: var(--erp-sidebar-width);
            min-height: 100vh;
            background: #f4f6f9;
        }

        /* ── Top bar ─────────────────────────────── */
        #topbar {
            height: var(--erp-topbar-height);
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        /* ── Stat cards ──────────────────────────── */
        .stat-card {
            border: none;
            border-radius: .5rem;
            transition: transform .15s, box-shadow .15s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important;
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: .5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        /* ── Queue table ─────────────────────────── */
        .queue-table th {
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #6c757d;
            border-top: none;
        }

        .queue-table td {
            vertical-align: middle;
        }

        /* ── Capacity bar ────────────────────────── */
        .capacity-bar .progress {
            height: 12px;
            border-radius: 6px;
        }

        /* ── Overtime alert ──────────────────────── */
        .overtime-alert {
            border-left: 4px solid #dc3545;
            background: #fff5f5;
        }

        /* ── Health dot ──────────────────────────── */
        .health-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        /* ── Responsive: collapse sidebar on mobile  */
        @media (max-width: 991.98px) {
            #sidebar {
                transform: translateX(-100%);
            }

            #sidebar.show {
                transform: translateX(0);
            }

            #main-content {
                margin-left: 0;
            }
        }

        /* ── Section headings ────────────────────── */
        .section-title {
            font-size: .875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: .5rem;
            margin-bottom: 1rem;
        }

        /* ── Days badge ──────────────────────────── */
        .days-chip {
            font-size: .7rem;
            padding: .2rem .5rem;
            border-radius: 999px;
            font-weight: 600;
        }
    </style>

    @stack('styles')
</head>
<body>

{{-- ═══════════════════════════════════════════════ --}}
{{-- SIDEBAR                                          --}}
{{-- ═══════════════════════════════════════════════ --}}
<nav id="sidebar">
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <i class="bi bi-scissors me-2"></i> ERP Classico
    </a>

    <ul class="nav flex-column py-2">

        <li class="nav-item">
            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>

        @auth
            @if(auth()->user()->isPipelineManager())
                <li><span class="nav-section">Management</span></li>
                <li class="nav-item">
                    <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                        <i class="bi bi-card-list"></i> All Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('capacity.index') }}" class="nav-link {{ request()->routeIs('capacity.*') ? 'active' : '' }}">
                        <i class="bi bi-sliders"></i> Capacity Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('notifications.create') }}" class="nav-link {{ request()->routeIs('notifications.create') ? 'active' : '' }}">
                        <i class="bi bi-megaphone"></i> Send Notification
                    </a>
                </li>
            @endif

            <li><span class="nav-section">Production</span></li>

            @if(auth()->user()->isPipelineManager() || auth()->user()->isDesigner())
                <li class="nav-item">
                    <a href="{{ route('dashboard.designer') }}" class="nav-link {{ request()->routeIs('dashboard.designer') ? 'active' : '' }}">
                        <i class="bi bi-pencil-square"></i> Design Queue
                    </a>
                </li>
            @endif

            @if(auth()->user()->isPipelineManager() || auth()->user()->isPrintingManager())
                <li class="nav-item">
                    <a href="{{ route('dashboard.printing') }}" class="nav-link {{ request()->routeIs('dashboard.printing') ? 'active' : '' }}">
                        <i class="bi bi-printer"></i> Print Queue
                    </a>
                </li>
            @endif

            @if(auth()->user()->isPipelineManager() || auth()->user()->isSewingManager())
                <li class="nav-item">
                    <a href="{{ route('dashboard.sewing') }}" class="nav-link {{ request()->routeIs('dashboard.sewing') ? 'active' : '' }}">
                        <i class="bi bi-scissors"></i> Sewing Queue
                    </a>
                </li>
            @endif

            <li><span class="nav-section">Reports</span></li>

            @if(auth()->user()->isPipelineManager())
                <li class="nav-item">
                    <a href="{{ route('history.index', ['department' => 'all']) }}"
                       class="nav-link {{ request()->routeIs('history.*') && request()->route('department') === 'all' ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i> All History
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.staff-performance') }}"
                       class="nav-link {{ request()->routeIs('reports.staff-performance') ? 'active' : '' }}">
                        <i class="bi bi-bar-chart-line"></i> Staff Performance
                    </a>
                </li>
            @endif

            @if(auth()->user()->isPipelineManager() || auth()->user()->isDesigner())
                <li class="nav-item">
                    <a href="{{ route('history.index', ['department' => 'design']) }}"
                       class="nav-link {{ request()->routeIs('history.*') && request()->route('department') === 'design' ? 'active' : '' }}">
                        <i class="bi bi-pencil-square"></i> Design History
                    </a>
                </li>
            @endif

            @if(auth()->user()->isPipelineManager() || auth()->user()->isPrintingManager())
                <li class="nav-item">
                    <a href="{{ route('history.index', ['department' => 'print']) }}"
                       class="nav-link {{ request()->routeIs('history.*') && request()->route('department') === 'print' ? 'active' : '' }}">
                        <i class="bi bi-printer"></i> Print History
                    </a>
                </li>
            @endif

            @if(auth()->user()->isPipelineManager() || auth()->user()->isSewingManager())
                <li class="nav-item">
                    <a href="{{ route('history.index', ['department' => 'sew']) }}"
                       class="nav-link {{ request()->routeIs('history.*') && request()->route('department') === 'sew' ? 'active' : '' }}">
                        <i class="bi bi-scissors"></i> Sewing History
                    </a>
                </li>
            @endif

            <li><span class="nav-section">Messages</span></li>
            <li class="nav-item">
                <a href="{{ route('notifications.inbox') }}"
                   class="nav-link {{ request()->routeIs('notifications.inbox') ? 'active' : '' }} d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-bell"></i> Notifications</span>
                    <span id="sidebar-notif-badge"
                          class="badge bg-danger rounded-pill"
                          style="font-size:.65rem;display:none"></span>
                </a>
            </li>
        @endauth

    </ul>

    @auth
        <div class="mt-auto p-3 border-top border-white border-opacity-10">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center"
                     style="width:32px;height:32px">
                    <i class="bi bi-person text-white"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="text-white fw-semibold text-truncate" style="font-size:.8rem">
                        {{ auth()->user()->name }}
                    </div>
                    <div class="text-white-50" style="font-size:.7rem">
                        {{ auth()->user()->role_label }}
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button class="btn btn-sm btn-outline-light w-100">
                    <i class="bi bi-box-arrow-left me-1"></i> Sign Out
                </button>
            </form>
        </div>
    @endauth
</nav>

{{-- ═══════════════════════════════════════════════ --}}
{{-- MAIN CONTENT                                     --}}
{{-- ═══════════════════════════════════════════════ --}}
<div id="main-content">

    {{-- Top bar --}}
    <header id="topbar" class="d-flex align-items-center px-3 px-lg-4 gap-3">
        {{-- Mobile sidebar toggle --}}
        <button class="btn btn-sm btn-outline-secondary d-lg-none"
                type="button"
                id="sidebarToggle">
            <i class="bi bi-list fs-5"></i>
        </button>

        {{-- Page title --}}
        <h1 class="h5 mb-0 fw-semibold text-truncate flex-grow-1">
            @yield('page-title', 'Dashboard')
        </h1>

        {{-- Date chip --}}
        <span class="badge bg-light text-secondary border d-none d-sm-inline-flex align-items-center gap-1">
            <i class="bi bi-calendar3"></i>
            {{ now()->format('D, d M Y') }}
        </span>

        @auth
            {{-- Notification bell --}}
            <a href="{{ route('notifications.inbox') }}"
               class="btn btn-sm btn-outline-secondary position-relative"
               title="Notifications">
                <i class="bi bi-bell fs-6"></i>
                <span id="notif-badge"
                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                      style="font-size:.6rem;display:none">0</span>
            </a>

            <div class="dropdown d-none d-lg-block">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i>
                    {{ auth()->user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <span class="dropdown-item-text small text-muted">
                            {{ auth()->user()->role_label }}
                        </span>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-left me-1"></i> Sign Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        @endauth
    </header>

    {{-- Flash messages --}}
    <div class="px-3 px-lg-4 pt-3">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{ session('error') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
    </div>

    {{-- Page content --}}
    <main class="px-3 px-lg-4 py-3 pb-5">
        @yield('content')
    </main>

</div>

{{-- Bootstrap 5 JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmzGVCcWhNxjnbEiP0UtzHnpUj15"
        crossorigin="anonymous"></script>

{{-- Sidebar toggle for mobile --}}
<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('show');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (e) {
        const sidebar = document.getElementById('sidebar');
        const toggle  = document.getElementById('sidebarToggle');
        if (sidebar.classList.contains('show')
            && !sidebar.contains(e.target)
            && !toggle.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    });
</script>

@auth
<script>
    // Poll for unread notification count every 60 seconds
    async function fetchNotifCount() {
        try {
            const res  = await fetch('{{ route("notifications.unread-count") }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            const count = data.count ?? 0;

            // Topbar bell badge
            const topBadge = document.getElementById('notif-badge');
            if (topBadge) {
                topBadge.textContent = count;
                topBadge.style.display = count > 0 ? '' : 'none';
            }

            // Sidebar badge
            const sideBadge = document.getElementById('sidebar-notif-badge');
            if (sideBadge) {
                sideBadge.textContent = count;
                sideBadge.style.display = count > 0 ? '' : 'none';
            }
        } catch (e) {}
    }

    fetchNotifCount();
    setInterval(fetchNotifCount, 60000);
</script>
@endauth

@stack('scripts')
</body>
</html>
