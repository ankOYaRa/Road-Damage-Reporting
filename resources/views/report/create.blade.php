@extends('layouts.app')
@section('title', 'Laporkan Kerusakan Jalan')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    #map { height: 320px; border-radius: 10px; border: 2px solid #e2e8f0; }
    .drop-zone {
        border: 2px dashed #2563eb; border-radius: 10px; padding: 2rem;
        text-align: center; cursor: pointer; transition: .2s;
        background: #f8fafc;
    }
    .drop-zone:hover { background: #eff6ff; }
    .photo-preview { display: none; text-align: center; margin-top: 1rem; }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card p-4">
            <h4 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Laporkan Kerusakan Jalan</h4>
            <p class="text-muted small mb-4">Isi formulir di bawah ini. Foto akan diverifikasi otomatis oleh sistem AI.</p>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 small">
                        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('report.store') }}" method="POST" enctype="multipart/form-data" id="reportForm">
                @csrf


                {{-- Lokasi --}}
                <h6 class="text-muted fw-semibold mb-3 border-bottom pb-1">Lokasi Kerusakan</h6>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Alamat / Keterangan Lokasi</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address') }}"
                           placeholder="Contoh: Jl. Sudirman No.5, depan toko ABC">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Koordinat GPS <span class="text-danger">*</span></label>
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <input type="number" id="latitude" name="latitude" class="form-control @error('latitude') is-invalid @enderror"
                                   step="any" placeholder="Latitude" value="{{ old('latitude') }}" required readonly>
                        </div>
                        <div class="col">
                            <input type="number" id="longitude" name="longitude" class="form-control @error('longitude') is-invalid @enderror"
                                   step="any" placeholder="Longitude" value="{{ old('longitude') }}" required readonly>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-primary" id="btnGps">
                                <i class="bi bi-geo-alt-fill"></i> Deteksi Lokasi
                            </button>
                        </div>
                    </div>
                    <div id="map"></div>
                    <small class="text-muted">Klik tombol atau klik langsung pada peta untuk menentukan lokasi.</small>
                    @error('latitude')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Deskripsi --}}
                <h6 class="text-muted fw-semibold mb-3 border-bottom pb-1">Detail Kerusakan</h6>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Deskripsi <span class="text-danger">*</span></label>
                    <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror"
                              placeholder="Jelaskan kondisi kerusakan jalan..." required>{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Foto --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold">Foto Kerusakan <span class="text-danger">*</span></label>
                    <div class="drop-zone" id="dropZone">
                        <i class="bi bi-camera fs-2 text-primary mb-2 d-block"></i>
                        <p class="mb-1">Klik atau seret foto ke sini</p>
                        <small class="text-muted">JPG, PNG, WEBP &mdash; maks. 5 MB</small>
                        <input type="file" name="photo" id="photoInput" accept="image/*"
                               class="@error('photo') is-invalid @enderror" style="display:none">
                    </div>
                    <div class="photo-preview" id="photoPreview">
                        <img id="preview-img" src="" alt="Preview" class="img-fluid mt-2">
                        <br><button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="removePhoto">
                            <i class="bi bi-x-circle"></i> Hapus foto
                        </button>
                    </div>
                    @error('photo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="bi bi-send-fill me-2"></i>Kirim Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// ── Map ──────────────────────────────────────────────────────────────────────
const defaultLat = -6.2088, defaultLng = 106.8456;
const map = L.map('map').setView([defaultLat, defaultLng], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

let marker;
function setLocation(lat, lng) {
    document.getElementById('latitude').value  = lat.toFixed(7);
    document.getElementById('longitude').value = lng.toFixed(7);
    if (marker) marker.setLatLng([lat, lng]);
    else marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    marker.on('dragend', e => {
        const p = e.target.getLatLng();
        document.getElementById('latitude').value  = p.lat.toFixed(7);
        document.getElementById('longitude').value = p.lng.toFixed(7);
    });
    map.setView([lat, lng], 16);
}

map.on('click', e => setLocation(e.latlng.lat, e.latlng.lng));

document.getElementById('btnGps').addEventListener('click', function() {
    if (!navigator.geolocation) {
        alert('Geolocation tidak didukung browser ini.');
        return;
    }

    const btn = this;
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Mencari lokasi...';

    navigator.geolocation.getCurrentPosition(
        pos => {
            setLocation(pos.coords.latitude, pos.coords.longitude);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        },
        err => {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            const messages = {
                1: 'Akses lokasi ditolak. Izinkan akses di pengaturan browser Anda.',
                2: 'Lokasi tidak dapat ditentukan. Coba lagi atau gunakan peta.',
                3: 'Timeout. Coba lagi atau gunakan peta.'
            };
            alert(messages[err.code] || 'Gagal mendapatkan lokasi.');
        },
        { timeout: 10000, enableHighAccuracy: false }
    );
});

// Pre-fill if old values exist
const oldLat = '{{ old("latitude") }}', oldLng = '{{ old("longitude") }}';
if (oldLat && oldLng) setLocation(parseFloat(oldLat), parseFloat(oldLng));

// ── Photo upload ─────────────────────────────────────────────────────────────
const dropZone   = document.getElementById('dropZone');
const photoInput = document.getElementById('photoInput');
const preview    = document.getElementById('photoPreview');
const previewImg = document.getElementById('preview-img');

dropZone.addEventListener('click', () => photoInput.click());
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = '#1d4ed8'; });
dropZone.addEventListener('dragleave', ()  => dropZone.style.borderColor = '#2563eb');
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    photoInput.files = e.dataTransfer.files;
    showPreview(photoInput.files[0]);
});
photoInput.addEventListener('change', () => showPreview(photoInput.files[0]));

function showPreview(file) {
    if (!file) return;
    previewImg.src = URL.createObjectURL(file);
    preview.style.display  = 'block';
    dropZone.style.display = 'none';
}

document.getElementById('removePhoto').addEventListener('click', () => {
    photoInput.value       = '';
    preview.style.display  = 'none';
    dropZone.style.display = 'block';
    previewImg.src         = '';
});

// ── Submit spinner ───────────────────────────────────────────────────────────
document.getElementById('reportForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled   = true;
    btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses dengan AI…';
});
</script>
@endpush
