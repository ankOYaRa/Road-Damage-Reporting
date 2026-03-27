@extends('layouts.admin')
@section('title', 'Detail Laporan #' . $report->id)

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="row g-4">
    {{-- Photo & CNN --}}
    <div class="col-lg-5">
        <div class="card p-3 h-100">
            <h6 class="fw-bold mb-3">Foto Kerusakan</h6>
            <img src="{{ asset('storage/' . $report->photo_path) }}" alt="foto kerusakan"
                 class="img-fluid rounded mb-3" style="max-height:320px;object-fit:cover;width:100%">

            <div class="p-3 rounded-3 border">
                <div class="fw-semibold mb-2"><i class="bi bi-cpu me-1 text-primary"></i>Hasil Analisis CNN</div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="badge rounded-pill fs-6 px-3 {{ $report->cnn_status === 'valid' ? 'badge-valid' : 'badge-invalid' }}">
                        {{ $report->cnn_status === 'valid' ? '✓ Foto Valid (Kerusakan Terdeteksi)' : '✗ Foto Tidak Valid' }}
                    </span>
                </div>
                <div class="progress mb-1" style="height:10px">
                    <div class="progress-bar {{ $report->cnn_status === 'valid' ? 'bg-success' : 'bg-danger' }}"
                         style="width: {{ $report->cnn_confidence * 100 }}%"></div>
                </div>
                <small class="text-muted">Tingkat keyakinan: <strong>{{ $report->confidencePercent() }}</strong></small>
            </div>
        </div>
    </div>

    {{-- Details & Action --}}
    <div class="col-lg-7">
        <div class="card p-3 mb-3">
            <h6 class="fw-bold mb-3">Informasi Laporan</h6>
            <table class="table table-sm table-borderless">
                <tr><td class="text-muted w-35">ID</td><td>#{{ $report->id }}</td></tr>
                <tr><td class="text-muted">Nama</td><td>{{ $report->name }}</td></tr>
                <tr><td class="text-muted">No. HP</td><td>{{ $report->phone ?: '-' }}</td></tr>
                <tr><td class="text-muted">Alamat</td><td>{{ $report->address ?: '-' }}</td></tr>
                <tr>
                    <td class="text-muted">Koordinat</td>
                    <td>
                        <a href="https://www.openstreetmap.org/?mlat={{ $report->latitude }}&mlon={{ $report->longitude }}&zoom=17"
                           target="_blank" class="text-decoration-none">
                            {{ $report->latitude }}, {{ $report->longitude }}
                            <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                        </a>
                    </td>
                </tr>
                <tr><td class="text-muted">Deskripsi</td><td>{{ $report->description }}</td></tr>
                <tr><td class="text-muted">Status Admin</td>
                    <td><span class="badge rounded-pill badge-{{ $report->admin_status }}">
                        {{ $report->admin_status === 'pending' ? 'Menunggu' : ($report->admin_status === 'approved' ? 'Disetujui' : 'Ditolak') }}
                    </span></td>
                </tr>
                @if($report->admin_note)
                <tr><td class="text-muted">Catatan Admin</td><td>{{ $report->admin_note }}</td></tr>
                @endif
                <tr><td class="text-muted">Dikirim</td><td>{{ $report->created_at->format('d M Y, H:i') }}</td></tr>
            </table>
        </div>

        @if($report->admin_status === 'pending')
        <div class="card p-3">
            <h6 class="fw-bold mb-3">Verifikasi Laporan</h6>
            <div class="row g-2">
                <div class="col-md-6">
                    <form method="POST" action="{{ route('admin.reports.approve', $report) }}">
                        @csrf
                        <div class="mb-2">
                            <textarea name="admin_note" class="form-control form-control-sm" rows="2"
                                      placeholder="Catatan (opsional)"></textarea>
                        </div>
                        <button class="btn btn-success w-100">
                            <i class="bi bi-check-lg me-1"></i>Setujui Laporan
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form method="POST" action="{{ route('admin.reports.reject', $report) }}">
                        @csrf
                        <div class="mb-2">
                            <textarea name="admin_note" class="form-control form-control-sm" rows="2"
                                      placeholder="Alasan penolakan (opsional)"></textarea>
                        </div>
                        <button class="btn btn-danger w-100">
                            <i class="bi bi-x-lg me-1"></i>Tolak Laporan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @else
        <div class="alert {{ $report->admin_status === 'approved' ? 'alert-success' : 'alert-danger' }}">
            <strong>{{ $report->admin_status === 'approved' ? '✓ Laporan Disetujui' : '✗ Laporan Ditolak' }}</strong>
            @if($report->admin_note) &mdash; {{ $report->admin_note }} @endif
        </div>
        @endif
    </div>
</div>
@endsection
