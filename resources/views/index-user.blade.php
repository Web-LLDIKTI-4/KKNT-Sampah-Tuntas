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
    <div class="card col">
        <div class="card-body">
            {{-- <x-button class="btn btn-sm btn-info modalButton" href="#modalku" data-bs-toggle="modal" data-src="{{ url('logkehadiran/tambahizin') }}" title="Laporan Izin"><i class="ri-calendar-todo-line pe-1"></i> Laporan Izin</x-button> --}}
            <div class="d-flex flex-column flex-md-row gap-3">
                <x-button class="btn-sm btn-secondary" modal="modalku" :modalSrc="url('logkehadiran/tambahizin')" title="Pengajuan Izin" :disabled="$kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional']) || $kehadiran && $kehadiran->waktu_masuk">
                    <i class="ri-add-line me-3"></i> 
                    Pengajuan Izin
                </x-button>

                <x-button id="btnTambahLog" class="btn-sm" style="background-color: black; color: white;" modal="modalku" :modalSrc="url('logkegiatan/tambah')" title="Tambah Log Harian" :disabled="$kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional'])">
                    <i class="ri-add-line me-3"></i> 
                    Tambah Log Harian
                </x-button>
            </div>
            
            <div class="divider">
                <div class="divider-text"><h4><i class="ri-calendar-todo-line"></i> {{ date("Y-m-d") }}</h4></div>
            </div>

            <div class="d-flex justify-content-center">
                <div class="row">
                    <div class="col ">
                        <form method="post" action="{{ url('logkehadiran/insert') }}" id="form-datang">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="mode" value="datang">
                            <input type="hidden" name="latitude" id="latitude-datang">
                            <input type="hidden" name="longitude" id="longitude-datang">
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
                            <input type="hidden" name="latitude" id="latitude-pulang">
                            <input type="hidden" name="longitude" id="longitude-pulang">
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
            @else
                <span id="lokasi_status" class="text-info d-flex justify-content-center mt-2"><i class="ri-loader-4-line spin-icon"></i> Mengambil lokasi...</span>
                <span id="waktu_error" class="text-danger d-flex justify-content-center mt-2"></span>
            @endif
            
            @if(!($kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional'])))
            <!-- Map dan Info Lokasi -->
            <div class="mt-4">
                <div id="distance_info" class="alert alert-info text-center" style="display:none;">
                    <div class="d-flex flex-column">
                        <small><i class="ri-map-pin-line"></i> <strong id="nearest_office_name"></strong></small>
                        <small>Jarak: <strong id="distance_text"></strong></small>
                        <small id="radius_status"></small>
                    </div>
                </div>
                <iframe id="map" style="width: 100%; height: 300px; border: 0; border-radius: 8px; display: none;" allowfullscreen loading="lazy"></iframe>
            </div>
            @endif
        </div>
    </div>
    <div class="card col align-self-start">
        <div class="card-widget-separator-wrapper">
            <div class="card-body card-widget-separator">
            <div class="d-flex flex-column gy-4 gy-sm-1 gap-5">
                <div class="col border-bottom">
                    <div class="d-flex justify-content-between align-items-start pb-3 card-widget-3">
                        <div>
                        <p class="mb-1">Log Harian</p>
                        <h4 class="mb-1">{{$jumlahlogkegiatan}}</h4>
                        @php
                            $persenkegiatan = round(($jumlahlogkegiatan/30)*100,1);
                        @endphp
                        <p class="mb-0">
                            <span class="me-2">({{$jumlahlogkegiatan}}/30)*100</span><span class="badge rounded-pill bg-label-success">{{$persenkegiatan}}%</span>
                        </p>
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
                        <h4 class="mb-1">{{ $jumlahlogbulanan }}</h4>
                        @php
                            $persenbulan = round(($jumlahlogbulanan/1)*100,1);
                        @endphp
                        <p class="mb-0">
                            <span class="me-2">({{$jumlahlogbulanan}}/1)*100</span><span class="badge rounded-pill bg-label-danger">{{$persenbulan}}%</span>
                        </p>
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
                                <h4 class="mb-1">{{ $jumlahcapaiankpi }}</h4>
                                @php
                                    if($jumlahcapaiankpi == 0){
                                        $persenjumlahcapaiankpi = 0;
                                    }else{
                                        $persenjumlahcapaiankpi = round(($jumlahcapaiankpi/5)*100,1);
                                    }
                                @endphp
                                <p class="mb-0">
                                    <span class="me-2">({{$jumlahcapaiankpi}})/5*100</span><span class="badge rounded-pill bg-label-danger">{{$persenjumlahcapaiankpi}}%</span>
                                </p>
                            </div>
                            <div class="avatar">
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
    // 1. DEKLARASI VARIABEL & KONFIGURASI
    // ==========================================
    
    // Elemen DOM
    var lokasiStatus = document.getElementById('lokasi_status');
    var waktuError = document.getElementById('waktu_error');
    var btnDatang = document.getElementById('btnSubmit_form-datang');
    var btnPulang = document.getElementById('btnSubmit_form-pulang');
    var btnTambahLog = document.getElementById('btnTambahLog');
    
    // Exit early jika elemen tidak ada (misalnya untuk admin atau sudah izin)
    if (!btnDatang || !btnPulang || !lokasiStatus) {
        return;
    }
    
    // Status aplikasi
    var isWithinRadius = false;          // Status apakah dalam radius kantor
    var radiusChecked = false;           // Status apakah sudah dicek radius
    var originalDatangDisabled = btnDatang.disabled;  // Status awal tombol datang
    var originalPulangDisabled = btnPulang.disabled;  // Status awal tombol pulang
    var originalTambahLogDisabled = btnTambahLog ? btnTambahLog.disabled : false;  // Status awal tombol tambah log

    var isFriday = (new Date()).getDay() === 5; // Jumat = 5
    var isExemptFromBaseLocationCheck = @json(Auth::user()->sistem_magang !== 'BDK'); // Mahasiswa wajib cek lokasi, selainnya tidak
    var isExemptFromLocationCheck = false; // Validasi lokasi WAJIB dilakukan
    
    // Koordinat kantor (diambil dari config)
    var officeLocations = @json(config('attendance.locations', []));
    
    var RADIUS_MAX = {{ config('attendance.radius_meter', 100) }};
    
    
    // ==========================================
    // 2. FUNGSI UTILITY (Perhitungan Jarak)
    // ==========================================
    
    /**
     * Menghitung jarak antara dua koordinat menggunakan Haversine formula
     * @returns {number} Jarak dalam meter
     */
    function calculateDistance(lat1, lng1, lat2, lng2) {
        var earthRadius = 6371000; // Radius bumi dalam meter
        var latFrom = lat1 * Math.PI / 180;
        var lngFrom = lng1 * Math.PI / 180;
        var latTo = lat2 * Math.PI / 180;
        var lngTo = lng2 * Math.PI / 180;
        
        var latDelta = latTo - latFrom;
        var lngDelta = lngTo - lngFrom;
        
        var a = Math.sin(latDelta / 2) * Math.sin(latDelta / 2) +
                Math.cos(latFrom) * Math.cos(latTo) *
                Math.sin(lngDelta / 2) * Math.sin(lngDelta / 2);
        
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        
        return earthRadius * c;
    }
    
    /**
     * Mencari kantor terdekat dari lokasi user
     * @returns {object} {office: {...}, distance: number} atau null
     */
    function findNearestOffice(userLat, userLng) {
        var nearest = null;
        var minDistance = Infinity;
        
        officeLocations.forEach(function(office) {
            var distance = calculateDistance(userLat, userLng, office.latitude, office.longitude);
            if (distance < minDistance) {
                minDistance = distance;
                nearest = {
                    office: office,
                    distance: distance
                };
            }
        });
        
        return nearest;
    }
    
    /**
     * Format jarak untuk ditampilkan
     * @returns {string} Contoh: "50 meter" atau "1.25 km"
     */
    function formatDistance(meters) {
        if (meters < 1000) {
            return Math.round(meters) + ' meter';
        } else {
            return (meters / 1000).toFixed(2) + ' km';
        }
    }
    
    
    // ==========================================
    // 3. FUNGSI VALIDASI
    // ==========================================
    
    
    /**
     * Cek apakah user dalam radius kantor
     * @returns {boolean} true jika dalam radius atau belum dicek
     */
    function isLocationValid() {
        if (isExemptFromLocationCheck) {
            return true; // Belum dicek, anggap tidak valid
        }
        if (!radiusChecked) {
            return false; // Belum dicek, anggap tidak valid
        }
        return isWithinRadius;
    }
    
    
    // ==========================================
    // 4. FUNGSI UPDATE UI
    // ==========================================
    
    /**
     * Update status tombol berdasarkan semua kondisi
     */
    function updateButtonStates() {
        var canUseButtons = true;
        var errorMessage = '';
        
        // Cek radius
        if (!isLocationValid()) {
            canUseButtons = false;
            if (radiusChecked) {
                errorMessage = '<i class="ri-map-pin-line"></i> Anda berada di luar radius kantor (maksimal ' + RADIUS_MAX + ' meter)';
            } else {
                errorMessage = '<i class="ri-loader-line"></i> Sedang memeriksa lokasi...';
            }
        }
        
        // Update pesan error
        if (waktuError) {
            waktuError.innerHTML = errorMessage;
            if (errorMessage) {
                waktuError.classList.add('text-danger');
            } else {
                waktuError.classList.remove('text-danger');
            }
        }
        
        // Update status tombol
        if (!canUseButtons) {
            // Disable semua tombol jika tidak memenuhi syarat
            btnDatang.disabled = true;
            btnPulang.disabled = true;
            btnDatang.title = errorMessage.replace(/<[^>]*>/g, ''); // Strip HTML untuk tooltip
            btnPulang.title = errorMessage.replace(/<[^>]*>/g, '');
            
            // Disable tombol tambah log jika ada
            if (btnTambahLog) {
                btnTambahLog.disabled = true;
                btnTambahLog.title = errorMessage.replace(/<[^>]*>/g, '');
            }
        } else {
            // Enable tombol sesuai status awal (logic izin/sakit/cuti & waktu absen)
            if (!originalDatangDisabled) {
                btnDatang.disabled = false;
                btnDatang.title = '';
            }
            if (!originalPulangDisabled) {
                btnPulang.disabled = false;
                btnPulang.title = '';
            }
            
            // Enable tombol tambah log jika ada dan sesuai status awal
            if (btnTambahLog && !originalTambahLogDisabled) {
                btnTambahLog.disabled = false;
                btnTambahLog.title = '';
            }
        }
    }
    
    /**
     * Update informasi jarak dan tampilan peta
     */
    function updateMapAndDistance(userLat, userLng) {
        var mapFrame = document.getElementById('map');
        var sistemMagangInfo = document.getElementById('sistem_magang_info');
        var distanceInfo = document.getElementById('distance_info');
        
        if (!mapFrame) {
            return;
        }

        // Tidak ada exception, abaikan kondisi if (isExemptFromLocationCheck)
        
        // Cari kantor terdekat
        var nearestData = findNearestOffice(userLat, userLng);
        
        if (nearestData && distanceInfo) {
            var distanceText = formatDistance(nearestData.distance);
            
            // Update status radius
            isWithinRadius = (nearestData.distance <= RADIUS_MAX);
            radiusChecked = true;
            
            // Update tampilan info jarak
            document.getElementById('nearest_office_name').textContent = nearestData.office.nama;
            document.getElementById('distance_text').textContent = distanceText;
            
            var radiusStatus = document.getElementById('radius_status');
            
            if (isWithinRadius) {
                radiusStatus.innerHTML = '<span class="badge bg-success"><i class="ri-check-line"></i> Dalam Radius</span>';
                distanceInfo.classList.remove('alert-danger');
                distanceInfo.classList.add('alert-success');
            } else {
                radiusStatus.innerHTML = '<span class="badge bg-danger"><i class="ri-close-line"></i> Di Luar Radius (Maks. ' + RADIUS_MAX + 'm)</span>';
                distanceInfo.classList.remove('alert-success');
                distanceInfo.classList.add('alert-danger');
            }
            
            distanceInfo.style.display = 'block';
        }
        
        // Tampilkan peta
        var mapUrl = 'https://maps.google.com/maps?q=' + userLat + ',' + userLng + '&z=16&output=embed';
        mapFrame.src = mapUrl;
        mapFrame.style.display = 'block';
    }
    
    /**
     * Update status lokasi di UI
     */
    function updateLocationStatus(status, message, isError) {
        if (!lokasiStatus) return;
        
        lokasiStatus.innerHTML = message;
        lokasiStatus.classList.remove('text-info', 'text-success', 'text-danger');
        
        if (isError) {
            lokasiStatus.classList.add('text-danger');
        } else if (status === 'success') {
            lokasiStatus.classList.add('text-success');
        } else {
            lokasiStatus.classList.add('text-info');
        }
    }
    
    
    // ==========================================
    // 5. VALIDASI FORM SUBMIT
    // ==========================================
    
    /**
     * Validasi sebelum form di-submit
     * @returns {boolean} true jika valid, false jika tidak
     */
    function validateBeforeSubmit(btnElement) {
        // Validasi 1: Cek radius
        if (!isLocationValid()) {
            alert('Anda berada di luar radius kantor (maksimal ' + RADIUS_MAX + ' meter dari kantor terdekat).');
            return false;
        }
        
        // Validasi 2: Cek koordinat sudah terisi
        var form = btnElement.closest('form');
        var lat = form.querySelector('[name="latitude"]').value;
        var lng = form.querySelector('[name="longitude"]').value;
        
        if (!lat || !lng) {
            alert('Lokasi belum terdeteksi. Pastikan GPS aktif dan izinkan akses lokasi.');
            return false;
        }
        
        return true;
    }
    
    
    // ==========================================
    // 6. EVENT HANDLERS
    // ==========================================
    
    // Handler untuk form datang
    document.getElementById('form-datang').addEventListener('submit', function(e) {
        if (!validateBeforeSubmit(btnDatang)) {
            e.preventDefault();
            return false;
        }
        btnDatang.disabled = true;
        btnDatang.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
    });
    
    // Handler untuk form pulang
    document.getElementById('form-pulang').addEventListener('submit', function(e) {
        if (!validateBeforeSubmit(btnPulang)) {
            e.preventDefault();
            return false;
        }
        btnPulang.disabled = true;
        btnPulang.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
    });
    
    
    // ==========================================
    // 7. INISIALISASI (Get User Location)
    // ==========================================
    
    // Disable tombol sementara saat mengambil lokasi
    if (!originalDatangDisabled) {
        btnDatang.disabled = true;
        btnDatang.title = 'Sedang mengambil lokasi...';
    }
    if (!originalPulangDisabled) {
        btnPulang.disabled = true;
        btnPulang.title = 'Sedang mengambil lokasi...';
    }
    if (btnTambahLog && !originalTambahLogDisabled) {
        btnTambahLog.disabled = true;
        btnTambahLog.title = 'Sedang mengambil lokasi...';
    }
    updateLocationStatus('loading', '<i class="ri-loader-line"></i> Mengambil lokasi...', false);
    
    // Cek dukungan geolocation
    if (!navigator.geolocation) {
        btnDatang.disabled = true;
        btnPulang.disabled = true;
        updateLocationStatus('error', '<i class="ri-error-warning-line"></i> Browser tidak mendukung geolocation.', true);
        return;
    }
    
    // Ambil lokasi user
    navigator.geolocation.getCurrentPosition(
        // Success callback
        function(position) {
            var userLat = position.coords.latitude;
            var userLng = position.coords.longitude;
            
            // Set koordinat ke hidden input
            var latDatang = document.getElementById('latitude-datang');
            var lngDatang = document.getElementById('longitude-datang');
            var latPulang = document.getElementById('latitude-pulang');
            var lngPulang = document.getElementById('longitude-pulang');
            
            if (latDatang) latDatang.value = userLat;
            if (lngDatang) lngDatang.value = userLng;
            if (latPulang) latPulang.value = userLat;
            if (lngPulang) lngPulang.value = userLng;
            
            // Update UI
            updateLocationStatus('success', '<i class="ri-map-pin-line text-success"></i> Lokasi terdeteksi', false);
            updateMapAndDistance(userLat, userLng);
            
            // Update status tombol berdasarkan semua validasi
            updateButtonStates();
        },
        // Error callback
        function(error) {
            var errorMsg = '';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMsg = 'Akses lokasi ditolak. Izinkan akses lokasi di browser.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMsg = 'Informasi lokasi tidak tersedia.';
                    break;
                case error.TIMEOUT:
                    errorMsg = 'Waktu permintaan lokasi habis.';
                    break;
                default:
                    errorMsg = 'Gagal mendapatkan lokasi.';
            }
            
            btnDatang.disabled = true;
            btnPulang.disabled = true;
            if (btnTambahLog) {
                btnTambahLog.disabled = true;
            }
            updateLocationStatus('error', '<i class="ri-error-warning-line"></i> ' + errorMsg, true);
        },
        // Options
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }
    );
})();
</script>
<x-script />
@stop 