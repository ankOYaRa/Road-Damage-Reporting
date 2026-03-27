<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Laporan Kerusakan Jalan')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f4f8; }
        .navbar-brand { font-weight: 700; letter-spacing: .5px; }
        .card { border: none; box-shadow: 0 2px 12px rgba(0,0,0,.08); border-radius: 12px; }
        .btn-primary { background: #2563eb; border-color: #2563eb; }
        #preview-img { max-height: 280px; object-fit: cover; border-radius: 8px; }
        .badge-valid   { background: #dcfce7; color: #16a34a; }
        .badge-invalid { background: #fee2e2; color: #dc2626; }
    </style>
    @stack('styles')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ route('report.create') }}">
            <i class="bi bi-cone-striped me-2"></i>Laporan Kerusakan Jalan
        </a>
    </div>
</nav>

<main class="container py-4">
    @yield('content')
</main>

<footer class="text-center text-muted py-3 small">
    &copy; {{ date('Y') }} Sistem Laporan Kerusakan Jalan &mdash; Powered by CNN
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
