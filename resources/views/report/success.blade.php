@extends('layouts.app')
@section('title', 'Laporan Terkirim')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card p-5 text-center">

            @if($report->cnn_status === 'valid')
                <div class="mb-3">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex p-4 mb-3">
                        <i class="bi bi-check-circle-fill text-success fs-1"></i>
                    </div>
                    <h4 class="fw-bold text-success">Laporan Terverifikasi AI</h4>
                    <p class="text-muted">Foto Anda dikenali sebagai <strong>kerusakan jalan</strong> oleh sistem CNN
                       dengan tingkat keyakinan <strong>{{ $report->confidencePercent() }}</strong>.</p>
                </div>
            @else
                <div class="mb-3">
                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex p-4 mb-3">
                        <i class="bi bi-exclamation-circle-fill text-warning fs-1"></i>
                    </div>
                    <h4 class="fw-bold text-warning">Foto Tidak Terdeteksi</h4>
                    <p class="text-muted">Sistem AI tidak mendeteksi kerusakan jalan pada foto yang dikirim
                       (keyakinan {{ $report->confidencePercent() }}).
                       Laporan tetap disimpan dan akan ditinjau oleh admin.</p>
                </div>
            @endif

            <hr>
            <div class="text-start small">
                <div class="row g-2">
                    <div class="col-5 text-muted">ID Laporan</div>
                    <div class="col-7 fw-semibold">#{{ $report->id }}</div>
                    <div class="col-5 text-muted">Nama</div>
                    <div class="col-7">{{ $report->name }}</div>
                    <div class="col-5 text-muted">Status CNN</div>
                    <div class="col-7">
                        <span class="badge rounded-pill {{ $report->cnn_status === 'valid' ? 'badge-valid' : 'badge-invalid' }}">
                            {{ $report->cnn_status === 'valid' ? '✓ Valid' : '✗ Tidak Valid' }}
                        </span>
                    </div>
                    <div class="col-5 text-muted">Status Admin</div>
                    <div class="col-7"><span class="badge bg-warning text-dark">Menunggu Verifikasi</span></div>
                    <div class="col-5 text-muted">Dikirim</div>
                    <div class="col-7">{{ $report->created_at->format('d M Y, H:i') }}</div>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('report.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Laporkan Kerusakan Lain
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
