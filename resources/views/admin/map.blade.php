@extends('layouts.admin')
@section('title', 'Peta Kerusakan Jalan')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    #map { height: calc(100vh - 160px); border-radius: 10px; }
    .info-panel { position: absolute; top: 10px; right: 10px; z-index: 1000;
                  background: white; border-radius: 10px; padding: 14px; min-width: 180px;
                  box-shadow: 0 2px 12px rgba(0,0,0,.15); }
    .legend-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
    .map-wrapper { position: relative; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-map-fill me-2 text-primary"></i>Peta Kerusakan Jalan</h5>
    <span class="badge bg-primary fs-6">{{ $reports->count() }} Titik Kerusakan Disetujui</span>
</div>

<div class="card p-2 map-wrapper">
    <div id="map"></div>
    <div class="info-panel">
        <div class="fw-semibold small mb-2">Legenda</div>
        <div class="d-flex align-items-center gap-2 small mb-1">
            <span class="legend-dot" style="background:#ef4444"></span> Kerusakan Jalan
        </div>
        <hr class="my-2">
        <div class="text-muted" style="font-size:.75rem">Hanya menampilkan laporan yang telah disetujui admin.</div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const reports = @json($reports);

const map = L.map('map').setView([-6.2088, 106.8456], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
}).addTo(map);

const icon = L.divIcon({
    className: '',
    html: `<div style="background:#ef4444;width:16px;height:16px;border-radius:50%;
                border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,.4)"></div>`,
    iconSize: [16, 16],
    iconAnchor: [8, 8],
});

const bounds = [];

reports.forEach(r => {
    const lat = parseFloat(r.latitude);
    const lng = parseFloat(r.longitude);
    bounds.push([lat, lng]);

    const marker = L.marker([lat, lng], { icon }).addTo(map);

    const confidence = (parseFloat(r.cnn_confidence) * 100).toFixed(1);
    const imgUrl = `{{ asset('storage') }}/${r.photo_path}`;
    const date   = new Date(r.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

    marker.bindPopup(`
        <div style="min-width:220px">
            <img src="${imgUrl}" style="width:100%;height:120px;object-fit:cover;border-radius:8px;margin-bottom:8px" onerror="this.style.display='none'">
            <div><strong>#${r.id} &mdash; ${r.name}</strong></div>
            <div class="text-muted small">${r.address || (lat.toFixed(5) + ', ' + lng.toFixed(5))}</div>
            <hr style="margin:6px 0">
            <div class="small">${r.description}</div>
            <div class="small text-muted mt-1">CNN: ${confidence}% &bull; ${date}</div>
        </div>
    `, { maxWidth: 260 });
});

if (bounds.length > 0) map.fitBounds(bounds, { padding: [50, 50] });
</script>
@endpush
