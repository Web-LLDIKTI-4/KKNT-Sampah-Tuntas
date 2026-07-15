@extends('layouts.app')
@section('title','Dashboard')
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
            <span class="align-middle">Dashboard</span>
        </h5>
        <span>Dashboard Mahasiswa</span>
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

            <div class="d-flex justify-content-center">
                <div class="row">
                    <div class="col ">
                        <form method="post" action="{{ url('logkehadiran/insert') }}" id="form-datang">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="mode" value="datang">
                            <input type="hidden" name="latitude_datang" id="latitude-datang">
                            <input type="hidden" name="longitude_datang" id="longitude-datang">
                            @php
                                // Logika: disabled jika izin/sakit/cuti atau waktu_masuk sudah ada
                                $disableDatang = ($kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional'])) || 
                                                 ($kehadiran && $kehadiran->waktu_masuk);
                            @endphp
                            <button type="submit" id="btnSubmit_form-datang" @if($disableDatang) disabled @endif class="btn btn-sm btn-primary">
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
                                // Logika: disabled jika izin/sakit/cuti, atau belum absen masuk, atau sudah absen pulang
                                $disablePulang = ($kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional'])) || 
                                                 (!$kehadiran || !$kehadiran->waktu_masuk) || 
                                                 ($kehadiran && $kehadiran->waktu_pulang);
                            @endphp
                            <button type="submit" id="btnSubmit_form-pulang" @if($disablePulang) disabled @endif class="btn btn-sm btn-danger">
                                <i class="ri-time-line pe-1"></i> Pulang
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @if($kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional']))
                <div class="alert alert-info d-flex align-items-center mt-3" role="alert">
                    <i class="ri-information-line me-2"></i>
                    <div>Anda sudah mengajukan <strong>{{ ucfirst($kehadiran->status_kehadiran) }}</strong> untuk hari ini.</div>
                </div>
            @endif

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
    var btnTambahLog = document.getElementById('btnTambahLog');

    if (!btnDatang || !btnPulang) {
        return;
    }

    var formDatang = document.getElementById('form-datang');
    var formPulang = document.getElementById('form-pulang');

    var latDatang = document.getElementById('latitude-datang');
    var lngDatang = document.getElementById('longitude-datang');
    var latPulang = document.getElementById('latitude-pulang');
    var lngPulang = document.getElementById('longitude-pulang');

    // ==========================================
    // Ambil lokasi user (hanya untuk dicatat & ditampilkan di peta, tidak untuk validasi)
    // ==========================================
    function fillCoordinates(lat, lng) {
        if (latDatang) latDatang.value = lat;
        if (lngDatang) lngDatang.value = lng;
        if (latPulang) latPulang.value = lat;
        if (lngPulang) lngPulang.value = lng;
    }

    function showMap(lat, lng) {
        var mapWrapper = document.getElementById('map_wrapper');
        var mapFrame = document.getElementById('map');
        var lokasiText = document.getElementById('lokasi_text');

        if (!mapWrapper || !mapFrame) return;

        // Embed tanpa API key: q=lat,lng otomatis menampilkan pin di titik tsb
        mapFrame.src = 'https://maps.google.com/maps?q=' + lat + ',' + lng + '&z=17&output=embed';
        mapWrapper.style.display = 'block';

        if (lokasiText) {
            lokasiText.innerHTML = '<i class="ri-map-pin-line text-success"></i> Lokasi anda: '
                + lat.toFixed(6) + ', ' + lng.toFixed(6);
        }
    }

    function showMapError(message) {
        var lokasiText = document.getElementById('lokasi_text');
        if (lokasiText) {
            lokasiText.innerHTML = '<i class="ri-error-warning-line text-danger"></i> ' + message;
        }
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                fillCoordinates(lat, lng);
                showMap(lat, lng);
            },
            function(error) {
                // Gagal ambil lokasi tidak menghalangi absen.
                // Field latitude/longitude dibiarkan kosong, peta tidak ditampilkan.
                showMapError('Gagal mengambil lokasi: ' + error.message);
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    } else {
        showMapError('Browser tidak mendukung geolocation.');
    }

    // ==========================================
    // Validasi sebelum submit (tanpa cek lokasi/radius)
    // ==========================================
    function validateBeforeSubmit(btnElement) {
        return true;
    }

    // ==========================================
    // Event Handlers
    // ==========================================
    if (formDatang) {
        formDatang.addEventListener('submit', function(e) {
            if (!validateBeforeSubmit(btnDatang)) {
                e.preventDefault();
                return false;
            }
            btnDatang.disabled = true;
            btnDatang.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
        });
    }

    if (formPulang) {
        formPulang.addEventListener('submit', function(e) {
            if (!validateBeforeSubmit(btnPulang)) {
                e.preventDefault();
                return false;
            }
            btnPulang.disabled = true;
            btnPulang.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
        });
    }
})();
</script>
@stop 