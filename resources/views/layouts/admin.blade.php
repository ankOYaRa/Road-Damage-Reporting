<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin &mdash; @yield('title', 'Dashboard')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f1f5f9; }
        .sidebar { min-height: calc(100vh - 56px); background: #1e293b; width: 240px; flex-shrink: 0; }
        .sidebar .nav-link { color: #94a3b8; padding: .6rem 1.2rem; border-radius: 6px; }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active { color: #fff; background: #334155; }
        .sidebar .nav-link i { width: 22px; }
        .main-content { flex: 1; min-width: 0; }
        .card { border: none; box-shadow: 0 1px 8px rgba(0,0,0,.07); border-radius: 10px; }
        .stat-card { border-left: 4px solid; }
        .badge-valid   { background: #dcfce7; color: #16a34a; }
        .badge-invalid { background: #fee2e2; color: #dc2626; }
        .badge-pending  { background: #fef9c3; color: #a16207; }
        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-rejected { background: #fee2e2; color: #b91c1c; }
    </style>
    @stack('styles')
</head>
<body>
<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand fw-bold"><i class="bi bi-cone-striped me-2"></i>Admin Panel</span>
    <div class="d-flex align-items-center gap-3">
        <span class="text-light small"><i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name ?? 'Admin' }}</span>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</button>
        </form>
    </div>
</nav>

<div class="d-flex">
    <aside class="sidebar p-3">
        <nav class="nav flex-column gap-1 mt-2">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               href="{{ route('admin.dashboard') }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <a class="nav-link {{ request()->routeIs('admin.map') ? 'active' : '' }}"
               href="{{ route('admin.map') }}">
                <i class="bi bi-map-fill"></i> Peta Kerusakan
            </a>
        </nav>
    </aside>

    <div class="main-content p-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
