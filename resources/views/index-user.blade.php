@extends('layouts.app')
@section('title','Dasbor')
@section('container')
<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin-icon {
    display: inline-block;
    margin-right: 10px;
    animation: spin 1s linear infinite;
}
</style>

<div class="d-flex mb-4 gap-4">
    <div class="avatar avatar-md">
        <div class="avatar-initial bg-label-primary rounded-4">
            <i class="ri-dashboard-3-fill ri-30px"></i>
        </div>
    </div>
    <div>
        <h5 class="mb-0">
            <span class="align-middle">Dasbor</span>
        </h5>
        <span>Dasbor Mahasiswa</span>
    </div>
</div>

<x-alert />

<div class="d-flex flex-column flex-md-row gap-5">
    <!-- Product List Widget -->
    {{-- Left Section --}}
    <div class="card col">
        <div class="card-body">
            {{-- <x-button class="btn btn-sm btn-info modalButton" href="#modalku" data-bs-toggle="modal" data-src="{{ url('logkehadiran/tambahizin') }}" title="Laporan Izin"><i class="ri-calendar-todo-line pe-1"></i> Laporan Izin</x-button> --}}
            <div class="d-flex flex-column flex-md-row gap-3">
                <x-button class="btn-sm btn-secondary" modal="modalku" :modalSrc="url('logkehadiran/tambahizin')" title="Pengajuan Izin" :disabled="$kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional']) || $kehadiran && $kehadiran->waktu_masuk">
                    <i class="ri-add-line me-2"></i> 
                    Pengajuan Izin
                </x-button>

                <x-button id="btnTambahLog" class="btn-sm" style="background-color: black; color: white;" modal="modalku" :modalSrc="url('logkegiatan/tambah')" title="Tambah Log Harian" :disabled="$kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional'])">
                    <i class="ri-add-line me-2"></i> 
                    Tambah Log Harian
                </x-button>
            </div>
            
            <div class="divider">
                <div class="divider-text"><h4><i class="ri-calendar-todo-line"></i> {{ date("d-m-Y") }}</h4></div>
            </div>

            <div class="d-flex justify-content-center gap-3 mb-3">
                <div class="col">
                    <form method="post" action="{{ url('logkehadiran/insert') }}" id="form-datang">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="mode" value="datang">
                        <input type="hidden" name="latitude_datang" id="latitude-datang">
                        <input type="hidden" name="longitude_datang" id="longitude-datang">
                        @php
                            $disableDatang = ($kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional'])) ||
                                            ($kehadiran && $kehadiran->waktu_masuk);
                        @endphp
                        <button type="submit" id="btnSubmit_form-datang"
                            {{ $disableDatang ? 'disabled' : 'disabled' }}
                            class="btn btn-sm btn-primary w-100">
                            <i class="ri-time-line pe-1"></i> Datang
                        </button>
                    </form>
                </div>
                <div class="col">
                    <form method="post" action="{{ url('logkehadiran/insert') }}" id="form-pulang">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="mode" value="pulang">
                        <input type="hidden" name="latitude_pulang" id="latitude-pulang">
                        <input type="hidden" name="longitude_pulang" id="longitude-pulang">
                        @php
                            $disablePulang = ($kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional'])) ||
                                            (!$kehadiran || !$kehadiran->waktu_masuk) ||
                                            ($kehadiran && $kehadiran->waktu_pulang);
                        @endphp
                        <button type="submit" id="btnSubmit_form-pulang"
                            {{ $disablePulang ? 'disabled' : 'disabled' }}
                            class="btn btn-sm btn-danger w-100">
                            <i class="ri-time-line pe-1"></i> Pulang
                        </button>
                    </form>
                </div>
            </div>

            @if($kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional']))
                <div class="alert alert-info d-flex align-items-center mt-3" role="alert">
                    <i class="ri-information-line me-2"></i>
                    <div>Anda sudah mengajukan <strong>{{ ucfirst($kehadiran->status_kehadiran) }}</strong> untuk hari ini.</div>
                </div>
            @endif

            {{-- Status lokasi --}}
            <div id="lokasi_status" class="w-100 mb-3">
                <div id="lokasi_checking" class="alert alert-info d-flex align-items-center gap-2 py-2 mb-0">
                    <span class="spinner-border spinner-border-sm"></span>
                    <span>Sedang mendeteksi lokasi Anda...</span>
                </div>
                <div id="lokasi_success" class="alert alert-success d-flex align-items-center gap-2 py-2 mb-0 d-none">
                    <i class="ri-map-pin-line"></i>
                    <span id="lokasi_coords"></span>
                </div>
                <div id="lokasi_error" class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-0 d-none">
                    <i class="ri-error-warning-line"></i>
                    <span id="lokasi_error_text"></span>
                </div>
                <div id="lokasi_unsupported" class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-0 d-none">
                    <i class="ri-error-warning-line"></i>
                    <span>Browser Anda tidak mendukung geolocation.</span>
                </div>
            </div>

            <div id="map_wrapper" style="display:none; margin-top: 1rem;">
                <div id="lokasi_text" class="mb-2 small text-muted"></div>
                <iframe
                    id="map"
                    width="100%"
                    height="250"
                    style="border:0; border-radius: 8px;"
                    loading="lazy"
                    allowfullscreen>
                </iframe>
            </div>
        </div>
    </div>

    {{-- Right Section --}}
    <div class="card col align-self-start">
        <div class="card-widget-separator-wrapper">
            <div class="card-body card-widget-separator">
                <div class="d-flex flex-column gy-4 gy-sm-1 gap-5">
                    <div class="col border-bottom">
                        <div class="d-flex justify-content-between align-items-start pb-3 card-widget-3">
                            <div>
                            <p class="mb-1">Log Harian</p>
                            <h4 class="mb-1">{{$jumlahlogkegiatan}} <span class="fs-5">Kegiatan</span></h4>
                            {{-- @php
                                $persenkegiatan = round(($jumlahlogkegiatan/30)*100,1);
                            @endphp --}}
                            {{-- <p class="mb-0">
                                <span class="me-2">{{$jumlahlogkegiatan}}</span>
                            </p> --}}
                            </div>
                            <div class="avatar me-sm-6">
                            <span class="avatar-initial rounded text-heading">
                                <i class="ri-bookmark-line ri-26px"></i>
                            </span>
                            </div>
                        </div>
                    </div>
                    <div class="col border-bottom">
                        <div class="d-flex justify-content-between align-items-start pb-3 card-widget-2">
                            <div>
                            <p class="mb-1">Log Bulanan</p>
                            <h4 class="mb-1">{{ $jumlahlogbulanan }} <span class="fs-5">Bulan</span></h4>
                            {{-- @php
                                $persenbulan = round(($jumlahlogbulanan/1)*100,1);
                            @endphp --}}
                            {{-- <p class="mb-0">
                                <span class="me-2">{{$jumlahlogbulanan}}</span>
                            </p> --}}
                            </div>
                            <div class="avatar me-lg-6">
                            <span class="avatar-initial rounded text-heading">
                                <i class="ri-book-line ri-26px"></i>
                            </span>
                            </div>
                        </div>
                    </div>
                    @if(Auth::user()->akses === "pjdesa")
                        <div class="col border-bottom">
                            <div class="d-flex justify-content-between align-items-start pb-3 card-widget-2">
                                <div>
                                    <p class="mb-1">Capaian KPI</p>
                                    <h4 class="mb-1">{{ $jumlahcapaiankpi }} <span class="fs-5">KPI</span></h4>
                                    {{-- @php
                                        if($jumlahcapaiankpi == 0){
                                            $persenjumlahcapaiankpi = 0;
                                        }else{
                                            $persenjumlahcapaiankpi = round(($jumlahcapaiankpi/5)*100,1);
                                        }
                                    @endphp --}}
                                    {{-- <p class="mb-0">
                                        <span class="me-2">({{$jumlahcapaiankpi}})</span>
                                    </p> --}}
                                </div>
                                <div class="avatar me-lg-6">
                                    <span class="avatar-initial rounded text-heading">
                                        <i class="ri-star-line ri-26px"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // ==========================================
    // Elemen DOM
    // ==========================================
    var btnDatang    = document.getElementById('btnSubmit_form-datang');
    var btnPulang    = document.getElementById('btnSubmit_form-pulang');
    var formDatang   = document.getElementById('form-datang');
    var formPulang   = document.getElementById('form-pulang');

    var latDatang    = document.getElementById('latitude-datang');
    var lngDatang    = document.getElementById('longitude-datang');
    var latPulang    = document.getElementById('latitude-pulang');
    var lngPulang    = document.getElementById('longitude-pulang');

    // Status elements
    var elChecking   = document.getElementById('lokasi_checking');
    var elSuccess    = document.getElementById('lokasi_success');
    var elError      = document.getElementById('lokasi_error');
    var elErrorText  = document.getElementById('lokasi_error_text');
    var elUnsupported = document.getElementById('lokasi_unsupported');
    var elCoords     = document.getElementById('lokasi_coords');

    if (!btnDatang || !btnPulang) return;

    // Kondisi disabled dari PHP — tidak boleh diubah JS
    var disableDatang = {{ $disableDatang ? 'true' : 'false' }};
    var disablePulang = {{ $disablePulang ? 'true' : 'false' }};

    // ==========================================
    // Helper: tampilkan status lokasi
    // ==========================================
    function showStatus(type, message) {
        // Sembunyikan semua dulu
        [elChecking, elSuccess, elError, elUnsupported].forEach(function(el) {
            if (el) {
                el.classList.add('d-none');
                el.classList.remove('d-flex');
            }
        });

        // Tampilkan yang sesuai
        var target = null;
        if (type === 'checking')    target = elChecking;
        if (type === 'success')     target = elSuccess;
        if (type === 'error')       target = elError;
        if (type === 'unsupported') target = elUnsupported;

        if (target) {
            target.classList.remove('d-none');
            target.classList.add('d-flex');
        }

        if (type === 'success' && elCoords)    elCoords.textContent    = message;
        if (type === 'error'   && elErrorText) elErrorText.textContent = message;
    }

    // ==========================================
    // Helper: isi koordinat ke hidden input
    // ==========================================
    function fillCoordinates(lat, lng) {
        if (latDatang) latDatang.value = lat;
        if (lngDatang) lngDatang.value = lng;
        if (latPulang) latPulang.value = lat;
        if (lngPulang) lngPulang.value = lng;
    }

    // ==========================================
    // Helper: tampilkan peta
    // ==========================================
    function showMap(lat, lng) {
        var mapWrapper = document.getElementById('map_wrapper');
        var mapFrame   = document.getElementById('map');

        if (!mapWrapper || !mapFrame) return;

        mapFrame.src = 'https://maps.google.com/maps?q=' + lat + ',' + lng + '&z=17&output=embed';
        mapWrapper.style.display = 'block';
    }

    // ==========================================
    // Helper: enable/disable button berdasarkan kondisi PHP
    // ==========================================
    function enableButtons() {
        if (!disableDatang) btnDatang.disabled = false;
        if (!disablePulang) btnPulang.disabled = false;
    }

    function disableButtons() {
        btnDatang.disabled = true;
        btnPulang.disabled = true;
    }

    // ==========================================
    // Geolocation
    // ==========================================
    if (navigator.geolocation) {
        showStatus('checking');
        disableButtons();

        navigator.geolocation.getCurrentPosition(
            function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;

                fillCoordinates(lat, lng);
                showMap(lat, lng);
                enableButtons();

                showStatus('success', 'Lokasi terdeteksi: ' + lat.toFixed(6) + ', ' + lng.toFixed(6));
            },
            function(error) {
                var message;
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        message = 'Izin lokasi ditolak. Mohon izinkan akses lokasi di browser Anda, lalu refresh halaman.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = 'Informasi lokasi tidak tersedia. Pastikan GPS Anda aktif.';
                        break;
                    case error.TIMEOUT:
                        message = 'Waktu deteksi lokasi habis. Silakan refresh halaman dan coba lagi.';
                        break;
                    default:
                        message = 'Gagal mengambil lokasi. Silakan refresh halaman.';
                }
                showStatus('error', message);
                disableButtons();
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    } else {
        showStatus('unsupported');
        disableButtons();
    }

    // ==========================================
    // Submit handler
    // ==========================================
    if (formDatang) {
        formDatang.addEventListener('submit', function() {
            btnDatang.disabled = true;
            btnDatang.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
        });
    }

    if (formPulang) {
        formPulang.addEventListener('submit', function() {
            btnPulang.disabled = true;
            btnPulang.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
        });
    }
})();
</script>
@stop 