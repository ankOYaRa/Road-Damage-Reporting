@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
{{-- Stats Row --}}
<div class="row g-3 mb-4">
    @php
        $statItems = [
            ['label' => 'Total Laporan',  'value' => $stats['total'],    'color' => '#2563eb', 'icon' => 'bi-file-earmark-text'],
            ['label' => 'Menunggu',       'value' => $stats['pending'],  'color' => '#d97706', 'icon' => 'bi-hourglass-split'],
            ['label' => 'Disetujui',      'value' => $stats['approved'], 'color' => '#16a34a', 'icon' => 'bi-check-circle'],
            ['label' => 'Ditolak',        'value' => $stats['rejected'], 'color' => '#dc2626', 'icon' => 'bi-x-circle'],
            ['label' => 'Foto Valid CNN', 'value' => $stats['valid'],    'color' => '#0891b2', 'icon' => 'bi-cpu'],
            ['label' => 'Foto Tdk Valid', 'value' => $stats['invalid'],  'color' => '#9333ea', 'icon' => 'bi-cpu-fill'],
        ];
    @endphp
    @foreach($statItems as $item)
    <div class="col-sm-6 col-xl-2">
        <div class="card p-3 stat-card h-100" style="border-left-color: {{ $item['color'] }}">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small mb-1">{{ $item['label'] }}</div>
                    <div class="fs-4 fw-bold">{{ $item['value'] }}</div>
                </div>
                <i class="bi {{ $item['icon'] }} fs-4" style="color:{{ $item['color'] }}"></i>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label small fw-semibold mb-1">Cari</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Nama, deskripsi, alamat…" value="{{ request('search') }}">
        </div>
        <div class="col-sm-2">
            <label class="form-label small fw-semibold mb-1">Status Admin</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Menunggu</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small fw-semibold mb-1">Status CNN</label>
            <select name="cnn" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="valid"   {{ request('cnn') === 'valid'   ? 'selected' : '' }}>Valid</option>
                <option value="invalid" {{ request('cnn') === 'invalid' ? 'selected' : '' }}>Tidak Valid</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search"></i> Filter
            </button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">#</th>
                    <th>Foto</th>
                    <th>Pelapor</th>
                    <th>Lokasi</th>
                    <th>CNN</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $r)
                <tr>
                    <td class="ps-3 text-muted small">#{{ $r->id }}</td>
                    <td>
                        <img src="{{ asset('storage/' . $r->photo_path) }}" alt="foto"
                             style="width:52px;height:42px;object-fit:cover;border-radius:6px;">
                    </td>
                    <td>
                        <div class="fw-semibold small">{{ $r->name }}</div>
                        <div class="text-muted" style="font-size:.75rem">{{ $r->phone }}</div>
                    </td>
                    <td class="small text-muted" style="max-width:140px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">
                        {{ $r->address ?: ($r->latitude . ', ' . $r->longitude) }}
                    </td>
                    <td>
                        <span class="badge rounded-pill {{ $r->cnn_status === 'valid' ? 'badge-valid' : 'badge-invalid' }}">
                            {{ $r->cnn_status === 'valid' ? '✓ Valid' : '✗ Tdk Valid' }}
                        </span>
                        <div class="text-muted" style="font-size:.7rem">{{ $r->confidencePercent() }}</div>
                    </td>
                    <td>
                        <span class="badge rounded-pill badge-{{ $r->admin_status }}">
                            {{ ucfirst($r->admin_status === 'pending' ? 'Menunggu' : ($r->admin_status === 'approved' ? 'Disetujui' : 'Ditolak')) }}
                        </span>
                    </td>
                    <td class="small text-muted">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-end pe-3">
                        <a href="{{ route('admin.reports.show', $r) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Tidak ada laporan ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reports->hasPages())
    <div class="p-3 border-top">{{ $reports->links() }}</div>
    @endif
</div>
@endsection
