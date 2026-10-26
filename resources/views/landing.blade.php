@php
    $lokasiProgramList ??= collect();
    $dataPesertaList ??= collect();
    $statusPeserta ??= [];
    $statusLokasi ??= [];
    $sebaranPtList ??= collect();
    $panduanList ??= collect();
    $faqList ??= collect();
    $fmt = fn ($v) => number_format((int) $v, 0, ',', '.');
    $stats = [
        ['value' => $jumlahMahasiswa ?? 0, 'label' => 'Mahasiswa', 'icon' => 'ri-group-line'],
        ['value' => $jumlahDpl ?? 0, 'label' => 'DPL', 'icon' => 'ri-user-star-line'],
        ['value' => $jumlahPt ?? 0, 'label' => 'Perguruan Tinggi', 'icon' => 'ri-building-4-line'],
        ['value' => $jumlahKecamatan ?? 0, 'label' => 'Kecamatan', 'icon' => 'ri-map-2-line'],
        ['value' => $jumlahKelurahan ?? 0, 'label' => 'Kelurahan/Desa', 'icon' => 'ri-home-8-line'],
    ];
    // Label nav = judul section (satu sumber, dipakai juga di <h1>/<h2>)
    $navItems = [
        'beranda' => 'KKN Tematik Sampah Tuntas',
        'lokasi' => 'Lokasi Pelaksanaan KKN Tematik Sampah Tuntas',
        'capaian' => 'Capaian Pengurangan Timbulan Sampah',
        'peserta' => 'Perguruan Tinggi Peserta',
        'sebaran' => 'Sebaran Perguruan Tinggi pada Wilayah Pelaksanaan Program',
        'peta' => 'Jejak Aksi Mahasiswa di Setiap Wilayah',
        'dokumen' => 'Panduan dan Dokumen Program',
    ];
    if ($faqList->isNotEmpty()) {
        $navItems['faq'] = 'Pertanyaan Umum';
    }
@endphp
<!doctype html>
<html lang="id" class="light-style layout-wide" dir="ltr" data-theme="theme-default" data-assets-path="{{ asset('assets') }}/" data-style="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>KKN Tematik Sampah Tuntas | LLDIKTI Wilayah IV</title>
    <meta name="description" content="Program GRADASI: KKN Tematik Sampah Tuntas LLDIKTI Wilayah IV capaian pengurangan sampah, lokasi program, data peserta, dan peta sebaran mahasiswa." />
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/icon.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/remixicon/remixicon.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/toastr/toastr.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/leaflet/leaflet.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/laporan.css') }}?v={{ filemtime(public_path('css/laporan.css')) }}" />
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}" />
    {{-- Hero entrance only; skipped for reduced motion --}}
    <script>if (!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches)) document.documentElement.classList.add('js-motion');</script>
</head>
<body class="lp">
    <a class="lp-skip" href="#main">Lewati ke konten</a>

    {{-- Navbar --}}
    <header class="lp-nav" id="lpNav">
        <div class="container-xxl lp-nav-inner">
            <a class="lp-brand" href="#beranda" aria-label="KKN Tematik Sampah Tuntas ke atas">
                <img src="{{ asset('assets/images/gradasi.png') }}" alt="" width="44" height="34" />
                <span class="lp-brand-text">
                    <strong>Sampah Tuntas</strong>
                    <small>KKN Tematik · LLDIKTI IV</small>
                </span>
            </a>

            <div class="lp-nav-actions">
                @auth
                    <a href="{{ route('home') }}" class="btn btn-primary lp-cta d-none d-sm-inline-flex">
                        <i class="ri-dashboard-line me-1" aria-hidden="true"></i> Ke Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary lp-cta d-none d-sm-inline-flex">
                        <i class="ri-login-box-line me-1" aria-hidden="true"></i> Masuk
                    </a>
                @endauth
                <button type="button" class="btn lp-burger"
                    aria-controls="lpOffcanvas" aria-label="Buka menu">
                    <i class="ri-menu-3-line" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </header>

    {{-- Menu mobile --}}
    <div class="offcanvas offcanvas-end lp-offcanvas" tabindex="-1" id="lpOffcanvas" aria-labelledby="lpOffcanvasTitle">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title h6 mb-0" id="lpOffcanvasTitle">Menu</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup menu"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="lp-offcanvas-menu" aria-label="Navigasi halaman (mobile)">
                @foreach ($navItems as $id => $label)
                    <a href="#{{ $id }}" class="lp-offcanvas-link" data-spy="{{ $id }}">{{ $label }}<i class="ri-arrow-right-s-line" aria-hidden="true"></i></a>
                @endforeach
            </nav>
            <div class="lp-offcanvas-cta">
                @auth
                    <a href="{{ route('home') }}" class="btn btn-primary w-100"><i class="ri-dashboard-line me-1" aria-hidden="true"></i> Ke Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100"><i class="ri-logout-box-r-line me-1" aria-hidden="true"></i> Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary w-100"><i class="ri-login-box-line me-1" aria-hidden="true"></i> Masuk</a>
                    <p class="small text-muted mt-3 mb-0">Belum punya akun?
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', config('helpdesk.whatsapp.0')) }}?text=Halo%20saya%20ingin%20bertanya" target="_blank" rel="noopener noreferrer">Hubungi LLDIKTI Wilayah IV</a>
                    </p>
                @endauth
            </div>
        </div>
    </div>

    <main id="main">
        {{-- Hero --}}
        <section class="lp-hero" id="beranda" tabindex="-1" aria-labelledby="heroTitle">
            <div class="container-xxl">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-5">
                        <img class="lp-hero-logos" src="{{ asset('assets/images/Logos-1200.png') }}" width="1200" height="211"
                            alt="Logo Pemprov Jawa Barat, pemerintah kabupaten/kota mitra, LLDIKTI Wilayah IV, dan Diktisaintek Berdampak" />
                        <p class="lp-eyebrow"><i class="ri-recycle-line" aria-hidden="true"></i> Program GRADASI · LLDIKTI Wilayah IV</p>
                        <h1 class="lp-hero-title" id="heroTitle">KKN Tematik <span>Sampah Tuntas</span></h1>
                        <p class="lp-hero-lead">
                            Ribuan Mahasiswa. Ratusan Kampus. Satu Gerakan Besar. <br />
                            Sampah Tuntas, <strong>Zero to TPA</strong>!
                        </p>
                        <div class="lp-hero-actions">
                            <a href="#capaian" class="btn btn-lg lp-btn-light">
                                <i class="ri-bar-chart-2-line me-1" aria-hidden="true"></i> Lihat Capaian
                            </a>
                            @auth
                                <a href="{{ route('home') }}" class="btn btn-lg lp-btn-ghost d-sm-none">Ke Dashboard <i class="ri-arrow-right-line ms-1" aria-hidden="true"></i></a>
                            @else
                                <a href="#lokasi" class="btn btn-lg lp-btn-ghost">Pilih Lokasi &amp; Masuk <i class="ri-arrow-right-line ms-1" aria-hidden="true"></i></a>
                            @endauth
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <figure class="lp-hero-figure mb-0">
                            <img src="{{ asset('assets/images/sampah.png') }}" width="1920" height="1080" fetchpriority="high"
                                alt="Infografis Program GRADASI Episode 1 - Sampah Tuntas: kolaborasi menuju Zero to TPA di Wilayah IV" />
                        </figure>
                    </div>
                </div>

                <ul class="lp-stats" aria-label="Ringkasan program">
                    @foreach ($stats as $stat)
                        <li class="lp-stat">
                            <i class="{{ $stat['icon'] }}" aria-hidden="true"></i>
                            <span class="lp-stat-value count-up" data-target="{{ (int) $stat['value'] }}">{{ $fmt($stat['value']) }}</span>
                            <span class="lp-stat-label">{{ $stat['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- SEBARAN LOKASI PROGRAM --}}
        <section class="lp-section" id="lokasi" tabindex="-1" aria-labelledby="lokasiTitle">
            <div class="container-xxl">
                <div class="lp-head" data-reveal>
                    <div>
                        <p class="lp-kicker">SEBARAN LOKASI PROGRAM</p>
                        <h2 class="lp-title" id="lokasiTitle">{{ $navItems['lokasi'] }}</h2>
                        <p class="lp-lead">Keterlibatan Perguruan Tinggi, Mahasiswa, dan DPL di setiap lokasi program.</p>
                    </div>
                    @if ($lokasiProgramList->count() > 4)
                        <div class="input-group input-group-merge lp-search">
                            <span class="input-group-text"><i class="ri-search-line" aria-hidden="true"></i></span>
                            <input type="search" id="lokasiSearch" class="form-control" placeholder="Cari lokasi…" aria-label="Cari lokasi program" aria-controls="lokasiGrid" autocomplete="off" />
                        </div>
                    @endif
                </div>

                @if ($lokasiProgramList->count())
                    <ul class="lokasi-grid list-unstyled mb-0" id="lokasiGrid" data-reveal-children>
                        @foreach ($lokasiProgramList as $lokasi)
                            <li class="lokasi-item" data-lokasi="{{ mb_strtolower($lokasi->nama_lokasi) }}">
                                <a href="{{ route('login', ['lokasi' => $lokasi->nama_lokasi]) }}" class="lokasi-link"
                                    aria-label="Masuk ke lokasi {{ $lokasi->nama_lokasi }}">
                                    <span class="lokasi-item-img">
                                        <img src="{{ $lokasi->gambar ? asset('storage/'.$lokasi->gambar) : asset('assets/images/placeholder.jpg') }}"
                                            alt="" loading="lazy" decoding="async" />
                                        <span class="lokasi-item-name"><i class="ri-map-pin-2-fill" aria-hidden="true"></i> {{ $lokasi->nama_lokasi }}</span>
                                    </span>
                                    <span class="lokasi-item-stats">
                                        <span class="lokasi-stat">
                                            <span class="lokasi-stat-value count-up" data-target="{{ (int) $lokasi->jumlah_dpl }}">{{ $fmt($lokasi->jumlah_dpl) }}</span>
                                            <span class="lokasi-stat-label">DPL</span>
                                        </span>
                                        <span class="lokasi-stat">
                                            <span class="lokasi-stat-value count-up" data-target="{{ (int) $lokasi->jumlah_mahasiswa }}">{{ $fmt($lokasi->jumlah_mahasiswa) }}</span>
                                            <span class="lokasi-stat-label">Mahasiswa</span>
                                        </span>
                                        <span class="lokasi-stat">
                                            <span class="lokasi-stat-value count-up" data-target="{{ (int) $lokasi->jumlah_pt }}">{{ $fmt($lokasi->jumlah_pt) }}</span>
                                            <span class="lokasi-stat-label">PTS</span>
                                        </span>
                                    </span>
                                    <span class="lokasi-item-cta">Masuk Dashboard Ini <i class="ri-arrow-right-line" aria-hidden="true"></i></span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <p id="lokasiNoResult" class="lp-empty d-none" aria-live="polite"><i class="ri-search-eye-line" aria-hidden="true"></i> Lokasi tidak ditemukan.</p>
                @else
                    <p class="lp-empty"><i class="ri-map-pin-line" aria-hidden="true"></i> Data lokasi kegiatan belum tersedia.</p>
                @endif
            </div>
        </section>

        {{-- KINERJA PROGRAM KEGIATAN --}}
        <section class="lp-section lp-section-alt" id="capaian" tabindex="-1" aria-labelledby="capaianTitle">
            <div class="container-xxl">
                <div class="lp-head" data-reveal>
                    <div>
                        <p class="lp-kicker">KINERJA PROGRAM KEGIATAN</p>
                        <h2 class="lp-title" id="capaianTitle">{{ $navItems['capaian'] }}</h2>
                        <p class="lp-lead">Pantau capaian pengurangan sampah pada setiap wilayah pelaksanaan KKN Tematik Sampah Tuntas, mulai dari tingkat kabupaten/kota hingga kecamatan dan kelurahan/desa. Telusuri kontribusi perguruan tinggi serta identifikasi tantangan pelaksanaan di lapangan.</p>
                    </div>
                </div>
                <div class="card lp-card" data-reveal>
                    <div class="card-body">
                        @isset($laporan)
                            <div data-drilldown="{{ route('login.laporan') }}" aria-live="polite">
                                @include('laporan._capaian_publik', $laporan)
                            </div>
                        @else
                            <p class="lp-empty mb-0">Data capaian belum tersedia.</p>
                        @endisset
                    </div>
                </div>
            </div>
        </section>

        {{-- PARTISIPASI PERGURUAN TINGGI --}}
        <section class="lp-section" id="peserta" tabindex="-1" aria-labelledby="pesertaTitle">
            <div class="container-xxl">
                <div class="lp-head" data-reveal>
                    <div>
                        <p class="lp-kicker">PARTISIPASI PERGURUAN TINGGI</p>
                        <h2 class="lp-title" id="pesertaTitle">{{ $navItems['peserta'] }}</h2>
                        <p class="lp-lead">Jelajahi keterlibatan perguruan tinggi melalui data mahasiswa, dosen pembimbing lapangan, penanggung jawab perguruan tinggi, dan ketua kelompok. Klik jumlah peserta untuk melihat informasi lebih lanjut.</p>
                        @if (($statusPeserta['mobilisasi']['total'] ?? 0) + ($statusPeserta['pelaksanaan']['total'] ?? 0) > 0)
                            <dl class="peserta-status">
                                @foreach (['mobilisasi' => ['Mobilisasi Kelompok', 'Sudah', 'Belum'], 'pelaksanaan' => ['Pelaksanaan KKN', 'Sudah isi', 'Belum isi']] as $statusKey => [$statusLabel, $doneLabel, $todoLabel])
                                    <div class="peserta-status-item">
                                        <dt>{{ $statusLabel }}</dt>
                                        <dd>
                                            @include('landing._status_badge', ['jenis' => $statusKey, 'status' => 'sudah', 'title' => $statusLabel, 'label' => $doneLabel, 'count' => $statusPeserta[$statusKey]['sudah'] ?? 0])
                                            @include('landing._status_badge', ['jenis' => $statusKey, 'status' => 'belum', 'title' => $statusLabel, 'label' => $todoLabel, 'count' => $statusPeserta[$statusKey]['belum'] ?? 0])
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </div>
                </div>
                <div class="card lp-card" data-reveal>
                    <div class="card-body">
                        @if (count($dataPesertaList))
                            <div class="peserta-toolbar">
                                <label class="peserta-limit" for="pesertaLimit">
                                    <span>Tampilkan</span>
                                    <select id="pesertaLimit" class="form-select form-select-sm" data-no-search>
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="0">Semua</option>
                                    </select>
                                    <span>data</span>
                                </label>
                                <div class="input-group input-group-sm peserta-search">
                                    <span class="input-group-text"><i class="ri-search-line" aria-hidden="true"></i></span>
                                    <input type="search" id="pesertaSearch" class="form-control" placeholder="Cari perguruan tinggi / kode PT…"
                                        aria-label="Cari perguruan tinggi" aria-controls="pesertaTbody" autocomplete="off" />
                                </div>
                                <x-button.export :url="route('login.data-peserta.export')" label="Unduh Data Excel" class="btn-sm" />
                            </div>
                            <div class="table-responsive peserta-table-wrap">
                                <table class="table table-sm table-hover align-middle peserta-table" data-sortable>
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-center">No</th>
                                            @foreach (['kodept' => 'Kode PT', 'nama' => 'Nama PTS', 'mahasiswa' => 'Mahasiswa', 'dpl' => 'DPL', 'pic' => 'PIC PTS', 'ketua' => 'Ketua Kelompok'] as $sortKey => $sortLabel)
                                                <th scope="col" class="text-center"><x-button.sort :type="in_array($sortKey, ['kodept', 'nama']) ? 'text' : 'num'">{{ $sortLabel }}</x-button.sort></th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody id="pesertaTbody" data-detail-url="{{ route('login.peserta') }}">
                                        @foreach ($dataPesertaList as $peserta)
                                            <tr data-search="{{ mb_strtolower($peserta->kodept.' '.$peserta->nama_pt) }}">
                                                <td class="text-center peserta-no">{{ $loop->iteration }}</td>
                                                <td class="text-center" data-sort-value="{{ $peserta->kodept }}">{{ $peserta->kodept }}</td>
                                                <td data-sort-value="{{ $peserta->nama_pt }}">{{ $peserta->nama_pt }}</td>
                                                @foreach (['mahasiswa' => $peserta->jumlah_mahasiswa, 'dpl' => $peserta->jumlah_dpl, 'pic' => $peserta->jumlah_pic ?? 0, 'ketua' => $peserta->jumlah_ketua_kelompok ?? 0] as $jenis => $jumlah)
                                                    <td class="text-center" data-sort-value="{{ (int) $jumlah }}">
                                                        @if ($jumlah > 0)
                                                            <button type="button" class="peserta-detail" data-kodept="{{ $peserta->kodept }}" data-jenis="{{ $jenis }}">{{ $fmt($jumlah) }}</button>
                                                        @else
                                                            {{ $fmt($jumlah) }}
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="fw-semibold border-top">
                                        <tr>
                                            <th colspan="3" class="text-center">Total</th>
                                            <th class="text-center">{{ $fmt($dataPesertaList->sum('jumlah_mahasiswa')) }}</th>
                                            <th class="text-center">{{ $fmt($dataPesertaList->sum('jumlah_dpl')) }}</th>
                                            <th class="text-center">{{ $fmt($dataPesertaList->sum('jumlah_pic')) }}</th>
                                            <th class="text-center">{{ $fmt($dataPesertaList->sum('jumlah_ketua_kelompok')) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                                <p id="pesertaNoResult" class="lp-empty d-none mb-0">Data tidak ditemukan.</p>
                            </div>
                            <div class="peserta-footer">
                                <span id="pesertaInfo" class="peserta-info" aria-live="polite"></span>
                                <nav aria-label="Navigasi halaman data peserta">
                                    <ul id="pesertaPagination" class="pagination pagination-sm"></ul>
                                </nav>
                            </div>
                        @else
                            <p class="lp-empty mb-0"><i class="ri-team-line" aria-hidden="true"></i> Data peserta belum tersedia.</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- SEBARAN PERGURUAN TINGGI: satu baris per pasangan PT-lokasi --}}
        <section class="lp-section lp-section-alt" id="sebaran" tabindex="-1" aria-labelledby="sebaranTitle">
            <div class="container-xxl">
                <div class="lp-head" data-reveal>
                    <div>
                        <p class="lp-kicker">SEBARAN PERGURUAN TINGGI</p>
                        <h2 class="lp-title" id="sebaranTitle">{{ $navItems['sebaran'] }}</h2>
                        <p class="lp-lead">Telusuri sebaran penugasan perguruan tinggi, mahasiswa, dan dosen pembimbing lapangan pada setiap wilayah pelaksanaan KKN Tematik Sampah Tuntas.</p>
                        @if (($statusLokasi['total'] ?? 0) + ($statusKetua['total'] ?? 0) > 0)
                            <dl class="peserta-status">
                                @if (($statusLokasi['total'] ?? 0) > 0)
                                    <div class="peserta-status-item">
                                        <dt>Mahasiswa Set Lokasi</dt>
                                        <dd>
                                            @include('landing._status_badge', ['jenis' => 'lokasi', 'status' => 'sudah', 'title' => 'Mahasiswa Set Lokasi', 'label' => 'Sudah', 'count' => $statusLokasi['sudah'] ?? 0])
                                            @include('landing._status_badge', ['jenis' => 'lokasi', 'status' => 'belum', 'title' => 'Mahasiswa Set Lokasi', 'label' => 'Belum', 'count' => $statusLokasi['belum'] ?? 0])
                                        </dd>
                                    </div>
                                @endif
                                @if (($statusKetua['total'] ?? 0) > 0)
                                    <div class="peserta-status-item">
                                        <dt>PTS Set Ketua Kelompok</dt>
                                        <dd>
                                            @include('landing._status_badge', ['jenis' => 'ketua', 'status' => 'sudah', 'title' => 'PTS Set Ketua Kelompok', 'label' => 'Sudah', 'count' => $statusKetua['sudah'] ?? 0])
                                            @include('landing._status_badge', ['jenis' => 'ketua', 'status' => 'belum', 'title' => 'PTS Set Ketua Kelompok', 'label' => 'Belum', 'count' => $statusKetua['belum'] ?? 0])
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        @endif
                    </div>
                </div>
                <div class="card lp-card" data-reveal>
                    <div class="card-body">
                        @if (count($sebaranPtList))
                            <div class="peserta-toolbar">
                                <label class="peserta-limit" for="sebaranLimit">
                                    <span>Tampilkan</span>
                                    <select id="sebaranLimit" class="form-select form-select-sm" data-no-search>
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="0">Semua</option>
                                    </select>
                                    <span>data</span>
                                </label>
                                <div class="input-group input-group-sm peserta-search">
                                    <span class="input-group-text"><i class="ri-search-line" aria-hidden="true"></i></span>
                                    <input type="search" id="sebaranSearch" class="form-control" placeholder="Cari PT / kode PT / lokasi / kecamatan / desa…"
                                        aria-label="Cari perguruan tinggi atau lokasi" aria-controls="sebaranTbody" autocomplete="off" />
                                </div>
                            </div>
                            <div class="table-responsive peserta-table-wrap">
                                <table class="table table-sm table-hover align-middle peserta-table sebaran-table" data-sortable>
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-center">No.</th>
                                            <th scope="col" class="text-center"><x-button.sort>Kode PT</x-button.sort></th>
                                            @foreach (['Nama PTS', 'Lokasi Program', 'Kecamatan', 'Kelurahan/Desa'] as $sortLabel)
                                                <th scope="col"><x-button.sort>{{ $sortLabel }}</x-button.sort></th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody id="sebaranTbody">
                                        @foreach ($sebaranPtList as $sebaran)
                                            <tr data-search="{{ mb_strtolower($sebaran->kodept.' '.$sebaran->nama_pt.' '.$sebaran->nama_lokasi.' '.$sebaran->nama_kecamatan.' '.$sebaran->nama_desa) }}">
                                                <td class="text-center peserta-no">{{ $loop->iteration }}</td>
                                                <td class="text-center">{{ $sebaran->kodept }}</td>
                                                <td>{{ $sebaran->nama_pt }}</td>
                                                <td>{{ $sebaran->nama_lokasi }}</td>
                                                <td>{{ $sebaran->nama_kecamatan ?? '-' }}</td>
                                                <td>{{ $sebaran->nama_desa ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <p id="sebaranNoResult" class="lp-empty d-none mb-0">Data tidak ditemukan.</p>
                            </div>
                            <div class="peserta-footer">
                                <span id="sebaranInfo" class="peserta-info" aria-live="polite"></span>
                                <nav aria-label="Navigasi halaman sebaran PT">
                                    <ul id="sebaranPagination" class="pagination pagination-sm"></ul>
                                </nav>
                            </div>
                        @else
                            <p class="lp-empty mb-0"><i class="ri-building-4-line" aria-hidden="true"></i> Data sebaran perguruan tinggi belum tersedia.</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- PETA SEBARAN (Leaflet dimuat saat section terlihat) --}}
        <section class="lp-section" id="peta" tabindex="-1" aria-labelledby="petaTitle">
            <div class="container-xxl">
                <div class="lp-head" data-reveal>
                    <div>
                        <p class="lp-kicker">PETA SEBARAN</p>
                        <h2 class="lp-title" id="petaTitle">{{ $navItems['peta'] }}</h2>
                        <p id="petaSubtitle" class="lp-lead">Memuat data…</p>
                    </div>
                    <div class="peta-filters" role="group" aria-label="Filter peta">
                        <select id="petaTahun" class="form-select form-select-sm" aria-label="Tahun"></select>
                        <select id="petaPt" class="form-select form-select-sm" aria-label="Perguruan tinggi"></select>
                        <button type="button" id="petaReset" class="btn btn-sm btn-outline-secondary">
                            <i class="ri-refresh-line me-1" aria-hidden="true"></i>Reset
                        </button>
                    </div>
                </div>

                <div class="card lp-card" data-reveal="fade">
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div id="petaMapWrap" class="peta-map-wrap" aria-busy="false">
                                    <div id="petaSebaran" class="peta-sebaran" data-url="{{ route('login.peta') }}" data-filter-url="{{ route('login.peta.filter') }}" role="region" aria-label="Peta sebaran mahasiswa"></div>
                                    <div id="petaStatus" class="peta-empty" aria-live="polite">
                                        <p id="petaStatusText" class="text-muted mb-0">Peta dimuat saat bagian ini terlihat…</p>
                                        <button type="button" id="petaRetry" class="btn btn-sm btn-primary d-none">Coba lagi</button>
                                    </div>
                                </div>
                                <details id="petaUnmapped" class="peta-unmapped d-none">
                                    <summary><i class="ri-map-pin-line" aria-hidden="true"></i><span id="petaUnmappedText"></span></summary>
                                    <ul id="petaUnmappedList" class="peta-unmapped-list list-unstyled mb-0"></ul>
                                </details>
                            </div>

                            <div class="col-lg-4">
                                <div class="peta-side">
                                    <div class="peta-summary" aria-live="polite">
                                        <span id="petaSummaryBadge" class="badge bg-primary"></span>
                                        <div id="petaTotal" class="peta-summary-value">–</div>
                                        <div id="petaSummaryNote" class="peta-muted"></div>
                                    </div>

                                    <div>
                                        <label for="petaSorot" class="form-label fw-semibold mb-1">Temukan Lokasi KKN</label>
                                        <select id="petaSorot" class="form-select form-select-sm">
                                            <option value="">-- Pilih Kelurahan/Desa untuk Fokus Peta --</option>
                                        </select>
                                    </div>

                                    <div class="peta-top">
                                        <div class="peta-top-head">
                                            <span><i class="ri-trophy-line text-warning me-1" aria-hidden="true"></i>5 Wilayah dengan Capaian Pengurangan Sampah Tertinggi</span>
                                            <small class="peta-muted fw-normal">Pilih wilayah untuk melihat lokasinya pada peta</small>
                                        </div>
                                        <ol id="petaTop" class="peta-top-list list-unstyled mb-0"></ol>
                                        <p id="petaTopEmpty" class="peta-muted mb-0 d-none">Belum ada data untuk filter ini.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="peta-top">
                                    <div class="peta-top-head">
                                        <span><i class="ri-recycle-line text-success me-1" aria-hidden="true"></i>Pengurangan Sampah per Kecamatan</span>
                                        <small id="petaKecPeriode" class="peta-muted fw-normal"></small>
                                    </div>
                                    <ul id="petaKec" class="peta-kec-list list-unstyled mb-0"></ul>
                                    <p id="petaKecEmpty" class="peta-muted mb-0 d-none">Belum ada data pengurangan sampah.</p>
                                </div>
                            </div>

                            <div class="col-12">
                                <p class="peta-tip mb-0">
                                    <i class="ri-lightbulb-line" aria-hidden="true"></i>
                                    <span><strong>Tips:</strong> klik lingkaran kelurahan/desa pada peta untuk melihat detail.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- DOKUMEN --}}
        <section class="lp-section lp-section-alt" id="dokumen" tabindex="-1" aria-labelledby="dokumenTitle">
            <span id="panduan" class="lp-anchor" aria-hidden="true"></span>
            <div class="container-xxl">
                <div class="lp-head" data-reveal>
                    <div>
                        <p class="lp-kicker">DOKUMEN</p>
                        <h2 class="lp-title" id="dokumenTitle">{{ $navItems['dokumen'] }}</h2>
                        <p class="lp-lead">Akses panduan, materi pembekalan, dokumen resmi, dan informasi teknis sebagai referensi pelaksanaan KKN Tematik Sampah Tuntas.</p>
                    </div>
                </div>
                <div data-reveal>
                    @include('panduan._publik', ['panduanList' => $panduanList])
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        @if ($faqList->isNotEmpty())
            <section class="lp-section" id="faq" tabindex="-1" aria-labelledby="faqTitle">
                <div class="container-xxl">
                    <div class="faq-layout">
                        <div class="lp-head" data-reveal>
                            <div>
                                <p class="lp-kicker">FAQ</p>
                                <h2 class="lp-title" id="faqTitle">{{ $navItems['faq'] }}</h2>
                                <p class="lp-lead">Pertanyaan Umum dan jawaban terkait program KKN Tematik Sampah Tuntas.</p>
                            </div>
                        </div>
                        <div data-reveal>
                            @include('faq._publik', ['faqList' => $faqList])
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </main>

    <footer class="lp-footer">
        <div class="container-xxl">
            <div class="lp-footer-grid" data-reveal-children>
                <div class="lp-footer-brand lp-footer-col">
                    <div class="lp-footer-logos">
                        <img src="{{ asset('assets/images/Logos-1200.png') }}" width="1200" height="211" loading="lazy"
                            alt="Logo Pemprov Jawa Barat, pemerintah kabupaten/kota mitra, LLDIKTI Wilayah IV, dan Diktisaintek Berdampak" />
                    </div>
                    <p class="mb-0">Program GRADASI: KKN Tematik Sampah Tuntas LLDIKTI WIlayah IV. <br/> Kolaborasi Perguruan Tinggi dan Pemerintah Daerah dalam Social Engineering Pengelolaan Sampah di Metropolitan Bandung Raya melalui KKN Tematik Sampah Tuntas</p>
                </div>
                <div class="lp-footer-col">
                    <h6 class="footer-title">INFORMASI ATAU ADA KENDALA?</h6>
                    <ul class="list-unstyled mb-0" aria-label="Help Desk">
                        <li>
                            <a class="footer-link lp-footer-wa" href="https://drive.google.com/file/d/1qBTNBqoouJ_8rD18whvf_lf4czwHsj8C/view?usp=sharing" target="_blank" rel="noopener noreferrer" title="Help Desk">
                                <i class="ri-whatsapp-line" aria-hidden="true"></i><span>Hubungi Help Desk</span>
                            </a>
                        </li>
                    </ul>
                </div>
                @include('layouts.footer-info', ['cols' => ['lp-footer-col', 'lp-footer-col']])
            </div>
            <div class="lp-footer-copy" data-reveal>
                <p class="mb-0">&copy; {{ date('Y') }}, LLDIKTI Wilayah IV</p>
                <div class="lp-social">
                    <a href="https://www.facebook.com/lldiktiwilayah4/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="ri-facebook-fill" aria-hidden="true"></i></a>
                    <a href="https://x.com/lldiktiwilayah4" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)"><i class="ri-twitter-x-line" aria-hidden="true"></i></a>
                    <a href="https://www.youtube.com/c/LLDIKTIWILAYAH4" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="ri-youtube-fill" aria-hidden="true"></i></a>
                    <a href="https://www.instagram.com/lldiktiwilayah4" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="ri-instagram-fill" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
    </footer>

    {{-- Modal detail data peserta --}}
    <div class="modal fade" id="modalPesertaDetail" tabindex="-1" aria-labelledby="modalPesertaDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPesertaDetailTitle">Detail Peserta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="peserta-detail-status lp-empty mb-0" aria-live="polite"></p>
                    <div class="peserta-detail-wrap d-none">
                        <div class="peserta-toolbar">
                            <label class="peserta-limit" for="pesertaDetailLimit">
                                <span>Tampilkan</span>
                                <select id="pesertaDetailLimit" class="form-select form-select-sm" data-no-search>
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span>data</span>
                            </label>
                            <div class="input-group input-group-sm peserta-search">
                                <span class="input-group-text"><i class="ri-search-line" aria-hidden="true"></i></span>
                                <input type="search" id="pesertaDetailSearch" class="form-control" placeholder="Cari…"
                                    aria-label="Cari data peserta" aria-controls="pesertaDetailTbody" autocomplete="off" />
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead><tr></tr></thead>
                                <tbody id="pesertaDetailTbody"></tbody>
                            </table>
                        </div>
                        <p class="peserta-detail-noresult lp-empty d-none mb-0">Data tidak ditemukan.</p>
                        <div class="peserta-footer">
                            <span id="pesertaDetailInfo" class="peserta-info" aria-live="polite"></span>
                            <nav aria-label="Navigasi halaman detail peserta">
                                <ul id="pesertaDetailPagination" class="pagination pagination-sm"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal daftar status (mobilisasi, pelaksanaan, set lokasi, ketua): paginasi & pencarian di server --}}
    <div class="modal fade" id="modalStatusList" tabindex="-1" aria-labelledby="modalStatusListTitle" aria-hidden="true"
        data-url="{{ route('login.status-list') }}">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalStatusListTitle">Daftar Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="status-list-status lp-empty mb-0" role="status" aria-live="polite"></div>
                    <div class="status-list-wrap d-none">
                        <div class="peserta-toolbar">
                            <label class="peserta-limit" for="statusListLimit">
                                <span>Tampilkan</span>
                                <select id="statusListLimit" class="form-select form-select-sm" data-no-search>
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                </select>
                                <span>data</span>
                            </label>
                            <div class="input-group input-group-sm peserta-search">
                                <span class="input-group-text"><i class="ri-search-line" aria-hidden="true"></i></span>
                                <input type="search" id="statusListSearch" class="form-control" placeholder="Cari nama / NIM / PT…"
                                    maxlength="50" aria-label="Cari data" aria-controls="statusListTbody" autocomplete="off" />
                            </div>
                        </div>
                        <div class="table-responsive status-list-table">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead><tr></tr></thead>
                                <tbody id="statusListTbody"></tbody>
                            </table>
                        </div>
                        <div class="status-list-noresult lp-empty d-none mb-0" role="status" aria-live="polite"></div>
                        <div class="peserta-footer">
                            <span id="statusListInfo" class="peserta-info" aria-live="polite"></span>
                            <nav aria-label="Navigasi halaman daftar status">
                                <ul id="statusListPagination" class="pagination pagination-sm"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/toastr/toastr.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('js/search-select.js') }}?v={{ filemtime(public_path('js/search-select.js')) }}"></script>
    <script src="{{ asset('js/drilldown.js') }}?v={{ filemtime(public_path('js/drilldown.js')) }}"></script>
    <script src="{{ asset('js/table-sort.js') }}?v={{ filemtime(public_path('js/table-sort.js')) }}"></script>
    <script src="{{ asset('js/landing.js') }}?v={{ filemtime(public_path('js/landing.js')) }}"
        data-leaflet-src="{{ asset('assets/vendor/libs/leaflet/leaflet.js') }}" id="landingScript"></script>
</body>
</html>
