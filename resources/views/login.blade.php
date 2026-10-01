<!doctype html>

<html
  lang="en"
  class="light-style layout-wide customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../../assets/"
  data-template="vertical-menu-template"
  data-style="light">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0" />

    <title>KKN Tematik Sampah Tuntas | Masuk</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/images/icon.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="../../assets/vendor/fonts/remixicon/remixicon.css" />
    <link rel="stylesheet" href="../../assets/vendor/fonts/flag-icons.css" />

    <!-- Menu waves for no-customizer fix -->
    <link rel="stylesheet" href="../../assets/vendor/libs/node-waves/node-waves.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../../assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../../assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../../assets/vendor/libs/typeahead-js/typeahead.css" />
    <!-- Vendor -->
    <link rel="stylesheet" href="../../assets/vendor/libs/@form-validation/form-validation.css" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="../../assets/vendor/css/pages/page-auth.css" />
    <link rel="stylesheet" href="../../assets/vendor/libs/toastr/toastr.css" />

    <!-- Helpers -->
    <script src="../../assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="../../assets/vendor/js/template-customizer.js"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="../../assets/js/config.js"></script>

    <style>
      .authentication-wrapper {
          min-height: 100vh;
          background: #667eea;
          position: relative;
      }

      .authentication-wrapper::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: url('../../assets/images/bg-image.jpg') no-repeat center center;
          background-size: cover;
          opacity: 0.08;
          pointer-events: none;
      }

      .authentication-inner {
          position: relative;
          z-index: 1;
      }
      
        /* Panel Lokasi Kiri */
        .lokasi-panel {
          width: 100%;
          height: 100%;
          background: transparent;
          border-radius: 0;
          box-shadow: none;
          display: flex;
          flex-direction: column;
          overflow: auto;
        }

        .lokasi-panel-header {
          padding: 1.5rem 1.75rem 1rem;
          /* border-bottom: 1px solid rgba(255, 255, 255, 0.2); */
        }

        .auth-cover-brand {
          padding: 0.5rem 0.75rem;
          border-radius: 14px;
          background: rgba(255, 255, 255, 0.95);
          box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .auth-logo-img {
          height: 42px;
          width: auto;
          display: block;
        }

        .lokasi-header-logo {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          gap: 0.75rem;
          margin-bottom: 0.9rem;
        }

        .lokasi-header-logo img {
          width: 90px;
          height: auto;
          display: block;
          margin-bottom: 0.1rem;
        }

        .lokasi-hero-img {
          width: 100%;
          height: auto;
          display: block;
          margin-bottom: 1rem;
          border-radius: 18px;
          border: 1px solid rgba(255, 255, 255, 0.25);
          box-shadow: 0 18px 40px rgba(20, 24, 70, 0.3);
        }

        .lokasi-panel-title {
          margin-top: 3rem;
          color: #fff;
          font-family: 'Inter', sans-serif;
          font-weight: 900;
          font-size: 50px;
          line-height: 1.1;
          letter-spacing: -0.02em;
          text-shadow: 0 2px 16px rgba(20, 24, 70, 0.25);
        }

        .lokasi-panel-subtitle {
          color: rgba(255, 255, 255, 0.88);
          font-size: 1.1rem;
          line-height: 1.6;
          margin-bottom: 2rem;
          max-width: 46rem;
        }

        .lokasi-search .input-group-text,
        .lokasi-search .form-control {
          background: rgba(255, 255, 255, 0.14);
          border-color: rgba(255, 255, 255, 0.35);
          color: #fff;
        }

        .lokasi-search .form-control::placeholder {
          color: rgba(255, 255, 255, 0.75);
        }

        .lokasi-search .form-control:focus {
          box-shadow: none;
          border-color: rgba(255, 255, 255, 0.6);
        }

        .lokasi-grid-wrap {
          padding: 1.25rem 1.5rem 1.5rem;
          overflow: auto;
        }

        .lokasi-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
          gap: 1.25rem;
        }

        .lokasi-item {
          background: #fff;
          border-radius: 14px;
          overflow: hidden;
          box-shadow: 0 8px 22px rgba(20, 24, 70, 0.14);
          border: 1px solid rgba(255, 255, 255, 0.6);
          transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
          cursor: pointer;
          position: relative;
        }

        .lokasi-item:hover {
          transform: translateY(-4px);
          box-shadow: 0 18px 36px rgba(20, 24, 70, 0.28);
        }

        .lokasi-item:focus-visible {
          outline: 3px solid #fff;
          outline-offset: 3px;
        }

        /* State terpilih — dipakai kalau JS toggle class .active saat lokasi diklik */
        .lokasi-item.active {
          border-color: #667eea;
          box-shadow: 0 0 0 3px #fff, 0 0 0 6px rgba(102, 126, 234, 0.55), 0 18px 36px rgba(20, 24, 70, 0.28);
        }

        .lokasi-item.active::after {
          content: '\eb7a'; /* ri-check-fill (Remix Icon) */
          font-family: 'remixicon';
          position: absolute;
          top: 10px;
          right: 10px;
          width: 28px;
          height: 28px;
          background: #667eea;
          color: #fff;
          border: 2px solid #fff;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 0.9rem;
          box-shadow: 0 4px 12px rgba(20, 24, 70, 0.3);
          z-index: 2;
        }

        .lokasi-item-img {
          position: relative;
          width: 100%;
          height: 200px;
          overflow: hidden;
          background: linear-gradient(135deg, #667eea, #7c8ff0);
        }

        .lokasi-item-img img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          display: block;
          transition: transform .35s ease;
        }

        .lokasi-item:hover .lokasi-item-img img {
          transform: scale(1.08);
        }

        .lokasi-item-img::before {
          content: '';
          position: absolute;
          inset: 0;
          background: linear-gradient(to top, rgba(0,0,0,0.45) 0%, rgba(0,0,0,0) 55%);
          z-index: 1;
        }

        .lokasi-item-badge {
          position: absolute;
          bottom: 8px;
          left: 10px;
          right: 10px;
          color: #fff;
          font-size: 0.85rem;
          font-weight: 600;
          z-index: 2;
          display: flex;
          align-items: center;
          gap: 4px;
          text-shadow: 0 1px 3px rgba(0,0,0,0.4);
        }

        .lokasi-item-badge i {
          font-size: 0.9rem;
        }

        .lokasi-item-body {
          padding: 12px;
        }

        /* Nama sudah ditampilkan sebagai badge di atas gambar, jadi ini opsional/disembunyikan */
        .lokasi-item-name {
          display: none;
        }

        .lokasi-item-stats {
          display: flex;
          justify-content: space-between;
          gap: 6px;
        }

        .lokasi-stat {
          display: flex;
          flex-direction: column;
          align-items: center;
          flex: 1;
          padding: 8px 4px;
          background: rgba(102, 126, 234, 0.07);
          border: 1px solid rgba(102, 126, 234, 0.1);
          border-radius: 10px;
        }

        .lokasi-stat i {
          font-size: 0.95rem;
          color: #667eea;
          margin-bottom: 2px;
        }

        .lokasi-stat-value {
          font-weight: 700;
          font-size: 0.95rem;
          color: #1f2937;
          line-height: 1.2;
        }

        .lokasi-stat-label {
          font-size: 0.68rem;
          color: #6b7280;
          text-transform: uppercase;
          letter-spacing: 0.04em;
        }

        .lokasi-empty {
          text-align: center;
          color: rgba(255, 255, 255, 0.85);
          padding: 1rem;
        }

        .selected-lokasi-label {
          display: inline-flex;
          align-items: center;
          gap: 0.35rem;
          max-width: 100%;
          font-size: 0.8rem;
          font-weight: 600;
          color: #4f5fd8;
          background: rgba(102, 126, 234, 0.12);
          border: 1px solid rgba(102, 126, 234, 0.3);
          padding: 0.4rem 0.85rem;
          border-radius: 999px;
          margin: 0.5rem 0 0.75rem;
        }

        .selected-lokasi-label span {
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }

        .login-title {
          margin-bottom: 0.2rem;
          line-height: 1.1;
          font-size: 1.2rem;
          color: #1f2937;
          letter-spacing: -0.01em;
        }

      /* Form Section */
      .authentication-bg {
          background-color: rgba(255, 255, 255, 0.98);
          backdrop-filter: blur(20px);
          box-shadow: 0 20px 60px rgba(20, 24, 70, 0.25);
          border-radius: 24px 0px 0px 24px;
      }

      .form-control:focus {
          border-color: #667eea;
          box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.2);
      }

      .btn-primary {
          padding: 0.8rem 1.5rem;
          font-weight: 600;
          letter-spacing: 0.01em;
          width: 100%;
          background: #667eea;
          border: none;
          border-radius: 10px;
          box-shadow: 0 6px 18px rgba(102, 126, 234, 0.3);
          transition: all 0.3s ease;
      }

      .btn-primary:hover {
          transform: translateY(-2px);
          box-shadow: 0 10px 28px rgba(102, 126, 234, 0.45);
      }

      .btn-primary:focus-visible {
          outline: 3px solid rgba(102, 126, 234, 0.45);
          outline-offset: 2px;
      }

      .btn-primary:disabled {
          transform: none;
          opacity: 0.8;
      }

      .btn-icon {
          width: 38px;
          height: 38px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          transition: all 0.3s ease;
          color: #667eea;
          background: #f4f5ff;
          border: 1px solid #e7e7ff;
      }

      .btn-icon:hover,
      .btn-icon:focus-visible {
          transform: translateY(-3px);
          color: #fff;
          background: #667eea;
          box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
          border-color: #667eea;
      }

      @media (prefers-reduced-motion: reduce) {
          .lokasi-item,
          .lokasi-item-img img,
          .btn-primary,
          .btn-icon {
              transition: none;
          }

          .lokasi-item:hover,
          .lokasi-item:hover .lokasi-item-img img,
          .btn-primary:hover,
          .btn-icon:hover {
              transform: none;
          }
      }
      
      .form-floating-outline .form-control {
          height: calc(3.5rem + 2px);
      }
      
      /* Responsive */
      @media (max-width: 991px) {
          .authentication-bg {
              border-radius: 0px;
          }

          .lokasi-panel-title {
            font-family: 'Inter', sans-serif;
            font-weight: 900;
            font-size: 44px;
          }

      }

        @media (max-width: 1300px) {
          .lokasi-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
          }
        }

        @media (max-width: 750px) {
          .lokasi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));

          }
        }

        .auth-right-panel {
          position: relative;
        }

        /* Desktop: form tetap di tempat, hanya panel lokasi yang di-scroll */
        @media (min-width: 992px) {
          .auth-right-panel {
            position: sticky;
            top: 0;
            height: 100vh;
            align-self: flex-start;
            align-items: flex-start !important;
            overflow-y: auto;
          }

          .auth-right-panel > div {
            margin-top: auto;
            margin-bottom: auto;
          }
        }

        /* Mobile switch button (left/right view) */
        .auth-mobile-toggle {
          display: none;
        }

        @media (max-width: 750px) {
          .auth-mobile-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
            background: #667eea;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 999px;
            padding: 0.55rem 1.1rem;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35);
          }

          .auth-mobile-toggle:hover,
          .auth-mobile-toggle:focus {
            color: #fff;
          }

          .auth-left-panel {
            display: none;
            width: 100%;
            flex: 0 0 100%;
            max-width: 100%;
          }

          .authentication-inner.show-lokasi .auth-left-panel {
            display: flex;
          }

          .authentication-inner.show-lokasi .auth-right-panel {
            display: none;
          }

          .lokasi-hero-img {
            margin-top: 1rem;
          }
        }

        /* Toggle Home / Laporan Kegiatan di panel kiri */
        .panel-switch-wrap {
          padding: 1.5rem 1.75rem 0;
        }

        .panel-switch {
          display: inline-flex;
          gap: 0.25rem;
          padding: 0.3rem;
          background: rgba(255, 255, 255, 0.16);
          border: 1px solid rgba(255, 255, 255, 0.3);
          border-radius: 999px;
        }

        .panel-switch .nav-link {
          display: inline-flex;
          align-items: center;
          gap: 0.4rem;
          color: #fff;
          font-weight: 600;
          font-size: 0.9rem;
          padding: 0.5rem 1.1rem;
          border-radius: 999px;
          background: transparent;
          border: 0;
          transition: background-color .2s ease, color .2s ease;
        }

        .panel-switch .nav-link:hover {
          background: rgba(255, 255, 255, 0.14);
        }

        .panel-switch .nav-link.active {
          background: #fff;
          color: #4f5fd8;
          box-shadow: 0 4px 12px rgba(20, 24, 70, 0.18);
        }

        .panel-switch .nav-link:focus-visible {
          outline: 3px solid #fff;
          outline-offset: 2px;
        }

        .laporan-wrap {
          padding: 1.5rem 1.75rem;
        }

        .laporan-title {
          color: #fff;
          font-family: 'Inter', sans-serif;
          font-weight: 800;
          font-size: 2rem;
          letter-spacing: -0.02em;
          margin-bottom: 0.35rem;
        }

        .laporan-card {
          border: 0;
          border-radius: 14px;
          box-shadow: 0 8px 22px rgba(20, 24, 70, 0.14);
        }

        .laporan-card .table th {
          white-space: nowrap;
        }

        .laporan-kpi-name {
          font-weight: 600;
          color: #1f2937;
        }

        #tabelLaporanPt .col-lokasi { min-width: 160px; }
        #tabelLaporanPt .col-pt { min-width: 260px; }
        #tabelLaporanPt .col-kpi { min-width: 220px; }
        #tabelLaporanPt .col-kegiatan { min-width: 260px; }
        #tabelLaporanPt .col-wilayah { min-width: 200px; }

        #tabelLaporanPt .laporan-text {
          display: -webkit-box;
          -webkit-line-clamp: 2;
          line-clamp: 2;
          -webkit-box-orient: vertical;
          overflow: hidden;
        }

        @media (max-width: 750px) {
          /* beri ruang untuk tombol mobile yang fixed di kanan atas */
          .panel-switch-wrap {
            padding: 4.25rem 1rem 0;
          }

          .laporan-wrap {
            padding: 1.25rem 1rem;
          }
        }
      </style>


  </head>

  <body>
    <!-- Content -->

    <div class="authentication-wrapper authentication-cover">
      <!-- Mobile switch button -->
      <button type="button" id="authMobileToggle" class="auth-mobile-toggle d-lg-none">
        <i class="ri-map-pin-2-line"></i>
        <span id="authMobileToggleText">Lihat Lokasi Program</span>
      </button>
      <!-- /Logo -->
      <div class="authentication-inner row m-0">
        <!-- Left Section -->
        <div class="auth-left-panel d-lg-flex col-lg-7 col-xl-8 p-0">
          <div class="lokasi-panel">
            <div class="panel-switch-wrap">
              <div class="nav panel-switch" role="tablist" aria-label="Tampilan panel">
                <button type="button" class="nav-link active" id="tabHome" data-bs-toggle="pill" data-bs-target="#paneHome" role="tab" aria-controls="paneHome" aria-selected="true">
                  <i class="ri-home-4-line"></i> Home
                </button>
                <button type="button" class="nav-link" id="tabLaporan" data-bs-toggle="pill" data-bs-target="#paneLaporan" role="tab" aria-controls="paneLaporan" aria-selected="false">
                  <i class="ri-file-chart-line"></i> Laporan Kegiatan
                </button>
              </div>
            </div>

            <div class="tab-content p-0 bg-transparent shadow-none">
            <div class="tab-pane fade show active" id="paneHome" role="tabpanel" aria-labelledby="tabHome">
            <div class="lokasi-panel-header">
              <img src="../assets/images/sampah.png" alt="Kegiatan KKN" class="lokasi-hero-img" />
              <h2 class="lokasi-panel-title">KKN Tematik Sampah Tuntas <br /> LLDIKTI Wilayah IV</h2>

              <p class="lokasi-panel-subtitle">
                Silahkan <b>pilih Lokasi</b> terlebih dahulu sebelum Anda Login
              </p>
              {{-- <div class="input-group lokasi-search">
                <span class="input-group-text"><i class="ri-search-line"></i></span>
                <input type="text" id="lokasiSearch" class="form-control" placeholder="Cari lokasi program..." />
              </div> --}}
            </div>

            <div class="lokasi-grid-wrap">
              @if(isset($lokasiProgramList) && $lokasiProgramList->count())
                <div class="lokasi-grid" id="lokasiGrid">
                  @foreach($lokasiProgramList as $lokasi)
                    <div class="lokasi-item"
                         tabindex="0"
                         role="button"
                         aria-pressed="false"
                         data-lokasi="{{ strtolower($lokasi->nama_lokasi) }}"
                         data-lokasi-name="{{ $lokasi->nama_lokasi }}">

                      <div class="lokasi-item-img">
                        <img src="{{ $lokasi->gambar ? asset('storage/'.$lokasi->gambar) : asset('assets/images/placeholder.jpg') }}" 
                             alt="{{ $lokasi->nama_lokasi }}"
                             loading="lazy">
                        {{-- <img src="{{ asset('assets/images/placeholder.jpg') }}"
                             alt="{{ $lokasi->nama_lokasi }}"
                             loading="lazy"> --}}
                          <span class="lokasi-item-badge">
                            <i class="ri-map-pin-2-fill"></i> {{ $lokasi->nama_lokasi }}
                          </span>
                      </div>

                      <div class="lokasi-item-body">
                        <div class="lokasi-item-name">{{ $lokasi->nama_lokasi }}</div>

                        <div class="lokasi-item-stats">
                          <div class="lokasi-stat">
                            <i class="ri-user-star-line"></i>
                            <span class="lokasi-stat-value">{{ $lokasi->jumlah_dpl }}</span>
                            <span class="lokasi-stat-label">DPL</span>
                          </div>
                          <div class="lokasi-stat">
                            <i class="ri-group-line"></i>
                            <span class="lokasi-stat-value">{{ $lokasi->jumlah_mahasiswa }}</span>
                            <span class="lokasi-stat-label">Mahasiswa</span>
                          </div>
                          <div class="lokasi-stat">
                            <i class="ri-building-4-line"></i>
                            <span class="lokasi-stat-value">{{ $lokasi->jumlah_pt }}</span>
                            <span class="lokasi-stat-label">Perguruan Tinggi</span>
                          </div>
                        </div>
                      </div>

                    </div>
                  @endforeach
                </div>
                <div id="lokasiNoResult" class="lokasi-empty d-none mt-2">Lokasi tidak ditemukan.</div>
              @else
                <p class="lokasi-empty mb-0">Data lokasi kegiatan belum tersedia.</p>
              @endif
            </div>
            </div>

            @php
              $num = fn ($v) => number_format($v, 0, ',', '.');
              $badge = fn ($p) => $p >= 100 ? 'bg-label-success' : ($p >= 50 ? 'bg-label-warning' : 'bg-label-danger');
            @endphp
            <div class="tab-pane fade" id="paneLaporan" role="tabpanel" aria-labelledby="tabLaporan">
              <div class="laporan-wrap">
                <h2 class="laporan-title">Laporan Kegiatan</h2>
                <p class="lokasi-panel-subtitle mb-4">Rekap peserta dan capaian KPI KKN Tematik Sampah Tuntas</p>

                <div class="card laporan-card mb-4">
                  <div class="card-header">
                    <h5 class="mb-0">Sebaran Perguruan Tinggi</h5>
                  </div>
                  <div class="card-body">
                    <p class="mb-3">Total Perguruan Tinggi: <strong>{{ $num($laporan['perLokasiPt']->flatten(1)->unique('kodept')->count()) }}</strong></p>
                    <div class="table-responsive">
                      <table class="table table-sm table-bordered mb-0" id="tabelLaporanPt" data-group-label="PT">
                        <thead>
                          <tr>
                            <th class="col-lokasi">Lokasi Program</th>
                            <th class="text-center">No</th>
                            <th class="col-pt">Perguruan Tinggi</th>
                            <th class="text-center">Mahasiswa</th>
                            <th class="text-center">Kelompok</th>
                            <th class="text-center">DPL</th>
                            <th class="col-wilayah">Sebaran Kecamatan</th>
                            <th class="col-wilayah">Sebaran Kelurahan/Desa</th>
                            <th class="col-kpi">KPI</th>
                            <th class="col-kegiatan">Kegiatan</th>
                            <th class="text-center">Capaian Kegiatan</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse ($laporan['perLokasiPt'] as $namaLokasi => $ptList)
                            @if ($ptList->isEmpty())
                              <tr data-group="{{ $namaLokasi }}">
                                <td class="fw-medium"><span class="laporan-text" title="{{ $namaLokasi }}">{{ $namaLokasi }}</span><div class="small text-muted fw-normal">0 PT</div></td>
                                <td class="text-center">-</td>
                                <td colspan="9" class="text-center text-muted">Belum ada perguruan tinggi</td>
                              </tr>
                            @endif
                            @foreach ($ptList as $pt)
                              @php
                                $group = $namaLokasi.'|'.$pt->kodept;
                                $nomorPt = $loop->iteration;
                                $kegiatanPt = $pt->kpi->flatMap(fn ($kpi) => $kpi->kegiatan->map(fn ($row) => [$kpi, $row]));
                              @endphp
                              @forelse ($kegiatanPt as [$kpi, $row])
                                <tr data-group="{{ $group }}">
                                  <td data-merge="{{ $namaLokasi }}" class="align-top fw-medium"><span class="laporan-text" title="{{ $namaLokasi }}">{{ $namaLokasi }}</span><div class="small text-muted fw-normal">{{ $num($ptList->count()) }} PT</div></td>
                                  <td data-merge="{{ $group }}" class="text-center align-top">{{ $nomorPt }}</td>
                                  <td data-merge="{{ $group }}" class="align-top"><span class="laporan-text" title="{{ $pt->nama_pt }}">{{ $pt->nama_pt }}</span></td>
                                  <td data-merge="{{ $group }}" class="text-center align-top">{{ $num($pt->jumlah_mahasiswa) }}</td>
                                  <td data-merge="{{ $group }}" class="text-center align-top">{{ $num($pt->jumlah_kelompok) }}</td>
                                  <td data-merge="{{ $group }}" class="text-center align-top">{{ $num($pt->jumlah_dpl) }}</td>
                                  <td data-merge="{{ $group }}" class="align-top">{{ $pt->kecamatan ?: '-' }}</td>
                                  <td data-merge="{{ $group }}" class="align-top">{{ $pt->kelurahan ?: '-' }}</td>
                                  <td data-merge="{{ $group }}|{{ $kpi->id_kpi }}" class="align-top">
                                    <div class="laporan-kpi-name laporan-text" title="{{ $kpi->nama_kpi }}">{{ $kpi->nama_kpi }}</div>
                                    @if ($kpi->capaian === null)
                                      <span class="text-muted small">Belum ada data</span>
                                    @else
                                      <span class="badge rounded-pill {{ $badge($kpi->capaian) }} mt-1">{{ \App\Models\Kpicapaian::formatPersen($kpi->capaian) }}</span>
                                    @endif
                                  </td>
                                  <td><span class="laporan-text" title="{{ $row->kegiatan }}">{{ $row->kegiatan }}</span></td>
                                  <td class="text-center">
                                    @if ($row->capaian === null)
                                      -
                                    @else
                                      <span class="badge rounded-pill {{ $badge($row->capaian) }}">{{ \App\Models\Kpicapaian::formatPersen($row->capaian) }}</span>
                                    @endif
                                  </td>
                                </tr>
                              @empty
                                <tr data-group="{{ $group }}">
                                  <td data-merge="{{ $namaLokasi }}" class="align-top fw-medium"><span class="laporan-text" title="{{ $namaLokasi }}">{{ $namaLokasi }}</span><div class="small text-muted fw-normal">{{ $num($ptList->count()) }} PT</div></td>
                                  <td class="text-center">{{ $nomorPt }}</td>
                                  <td><span class="laporan-text" title="{{ $pt->nama_pt }}">{{ $pt->nama_pt }}</span></td>
                                  <td class="text-center">{{ $num($pt->jumlah_mahasiswa) }}</td>
                                  <td class="text-center">{{ $num($pt->jumlah_kelompok) }}</td>
                                  <td class="text-center">{{ $num($pt->jumlah_dpl) }}</td>
                                  <td>{{ $pt->kecamatan ?: '-' }}</td>
                                  <td>{{ $pt->kelurahan ?: '-' }}</td>
                                  <td colspan="3" class="text-center text-muted">Belum ada KPI</td>
                                </tr>
                              @endforelse
                            @endforeach
                          @empty
                            <tr><td colspan="11" class="text-center text-muted">Belum ada data perguruan tinggi</td></tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="card laporan-card">
                  <div class="card-header">
                    <h5 class="mb-1">Capaian KPI</h5>
                    <p class="mb-0 card-subtitle">Capaian KPI = rata-rata capaian kegiatan yang sudah punya data</p>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-sm table-bordered mb-0">
                        <thead>
                          <tr>
                            <th class="text-center">No</th>
                            <th>KPI</th>
                            <th>Kegiatan</th>
                            <th class="text-center">Capaian Kegiatan</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse ($laporan['perKpi'] as $kpi)
                            @foreach ($kpi->kegiatan as $row)
                              <tr>
                                @if ($loop->first)
                                  <td rowspan="{{ $loop->count }}" class="text-center align-top">{{ $loop->parent->iteration }}</td>
                                  <td rowspan="{{ $loop->count }}" class="align-top">
                                    <div class="laporan-kpi-name">{{ $kpi->nama_kpi }}</div>
                                    @if ($kpi->capaian === null)
                                      <span class="text-muted small">Belum ada data</span>
                                    @else
                                      <span class="badge rounded-pill {{ $badge($kpi->capaian) }} mt-1">{{ \App\Models\Kpicapaian::formatPersen($kpi->capaian) }}</span>
                                    @endif
                                  </td>
                                @endif
                                <td>{{ $row->kegiatan }}</td>
                                <td class="text-center">
                                  @if ($row->capaian === null)
                                    -
                                  @else
                                    <span class="badge rounded-pill {{ $badge($row->capaian) }}">{{ \App\Models\Kpicapaian::formatPersen($row->capaian) }}</span>
                                  @endif
                                </td>
                              </tr>
                            @endforeach
                          @empty
                            <tr><td colspan="4" class="text-center text-muted">Belum ada KPI</td></tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </div>
          </div>
        </div>
        <!-- /Left Section -->

        <!-- Login Form -->
        <div class="auth-right-panel d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg py-5 px-4 px-sm-5">
          <div class="w-100 mx-auto" style="max-width: 400px;">
            <div class="mb-4 text-center">
              {{-- Logo Header --}}
              <div class="d-flex justify-content-center">
                <div class="lokasi-header-logo">
                  <img src="../../assets/images/logo-kkn-berdampak.jpeg" alt="KKN Tematik Berdampak" />
                  <img src="../../assets/images/gradasi.png" alt="Gradasi 4" style="width: 110px; height: auto;" />
                </div>
              </div>
              <img src="../../assets/images/lldikti4_logo.png" alt="LLDIKTI Wilayah IV" style="width: 250px; height: auto; margin-bottom: 30px;" />
              
              <h4 class="login-title fw-bold">KKNT Sampah Tuntas</h4>
              <div id="selectedLokasiLabel" class="selected-lokasi-label d-none" aria-live="polite">
                <i class="ri-map-pin-2-fill"></i>
                <span id="selectedLokasiName"></span>
              </div>
              <p class="mb-0 text-muted">Silakan masuk untuk membuka Dashboard</p>
            </div>

            <form id="formAuthentication" class="mb-5" action="{{ url('login') }}" method="POST">
              @csrf
              @method('PUT')
              <input type="hidden" id="lokasi" name="lokasi" value="" />
              <div class="form-floating form-floating-outline mb-5">
                <input
                  type="text"
                  class="form-control"
                  id="email"
                  name="username"
                  placeholder="Masukkan email atau nama pengguna"
                  autofocus />
                <label for="email">Email</label>
              </div>
              <div class="mb-5">
                <div class="form-password-toggle">
                  <div class="input-group input-group-merge">
                    <div class="form-floating form-floating-outline">
                      <input
                        type="password"
                        id="password"
                        class="form-control"
                        name="password"
                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                        aria-describedby="password" />
                      <label for="password">Kata Sandi</label>
                    </div>
                    <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                  </div>
                </div>
              </div>
              <div class="mb-4 d-flex justify-content-between align-items-center">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="remember-me" />
                  <label class="form-check-label" for="remember-me">Ingat Saya</label>
                </div>
                {{-- <a href="#" class="text-primary">
                  <small>Lupa Kata Sandi?</small>
                </a> --}}
              </div>
              <button class="btn btn-primary" id="btnSubmit_formAuthentication">
                <i class="ri-lock-fill me-2"></i>Masuk
              </button>
            </form>

            <div class="text-center mt-4">
              <p class="mb-0">
                <span class="text-muted">Belum Punya Akun? Kontak </span>
                <a href="https://wa.me/6282244121226?text=Halo%20saya%20ingin%20bertanya" class="fw-semibold">LLDIKTI Wilayah IV</a>
              </p>
            </div>

            <div class="text-center mt-4">
              <div class="d-flex justify-content-center gap-2">
                <a href="https://www.facebook.com/lldiktiwilayah4/?tsid=0.24115179413463506&source=result" target="_blank" rel="noopener noreferrer" class="btn btn-icon rounded-circle btn-text-facebook" title="Facebook">
                  <i class="ri-facebook-fill"></i>
                </a>
                <a href="https://x.com/lldiktiwilayah4?s=09" target="_blank" rel="noopener noreferrer" class="btn btn-icon rounded-circle btn-text-twitter" title="Twitter">
                  <i class="ri-twitter-fill"></i>
                </a>
                <a href="https://www.youtube.com/c/LLDIKTIWILAYAH4" target="_blank" rel="noopener noreferrer" class="btn btn-icon rounded-circle btn-text-google-plus" title="YouTube">
                  <i class="ri-youtube-fill"></i>
                </a>
                <a href="https://www.instagram.com/lldiktiwilayah4?utm_medium=copy_link" target="_blank" rel="noopener noreferrer" class="btn btn-icon rounded-circle btn-text-google-plus" title="Instagram">
                  <i class="ri-instagram-fill"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
        <!-- /Login Form -->
      </div>
    </div>

    <!-- / Content -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <!--<script src="../../assets/vendor/libs/node-waves/node-waves.js"></script>-->
    <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../assets/vendor/libs/hammer/hammer.js"></script>
    <script src="../../assets/vendor/libs/i18n/i18n.js"></script>
    <script src="../../assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="../../assets/vendor/js/menu.js"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="../../assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="../../assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="../../assets/vendor/libs/@form-validation/auto-focus.js"></script>

    <!-- Main JS -->
    <script src="../../assets/js/main.js"></script>

    <script src="../../assets/vendor/libs/toastr/toastr.js"></script>
    <script src="{{ asset('js/grouped-table.js') }}?v={{ filemtime(public_path('js/grouped-table.js')) }}"></script>
  </body>
</html>
<script>
$(function(){
    GroupedTable.init(document.getElementById("tabelLaporanPt"));

    // Token CSRF diperbarui dari respons server, tanpa reload halaman
    function setToken(token) {
      if (!token) return;
      $("#formAuthentication input[name='_token']").val(token);
      $('meta[name="csrf-token"]').attr("content", token);
    }

    $("#formAuthentication").on("submit",function(e, retried){
      var form = $(this);
      var action = $(this).attr("action");
      var id = $(this).attr("id");
      var btnHtml = $("#btnSubmit_"+id+"").html();
      var dString = $(this).serialize();
      $.ajax({
        dataType:'json',
        type:'post',
        url:action,
        data:dString,
        beforeSend:function(){
          $("#btnSubmit_"+id+"").prop("disabled",true);
          $("#btnSubmit_"+id+"").html('<span class="spinner-border me-2" role="status" aria-hidden="true"></span> Loading...');			
        },
        complete:function(){
          $("#btnSubmit_"+id+"").prop("disabled",false);
          $("#btnSubmit_"+id+"").html(btnHtml);	
        },
        success:function(ret){
          if(ret.success == true){
            toastr.success(ret.messages)
            document.location = ret.redirect_url || "{{ url('home') }}";
          }else{
            setToken(ret.token)
            toastr.warning(ret.messages)
            if(ret.messages && ret.messages.indexOf("lokasi program") !== -1){
              $(".authentication-inner").addClass("show-lokasi");
              $("#authMobileToggleText").text("Kembali ke Formulir Masuk");
              $("#authMobileToggle i").removeClass("ri-map-pin-2-line").addClass("ri-arrow-left-line");
            }
          }
        },
        error:function(xhr){
          // 419 = sesi/token kedaluwarsa: ambil token baru lalu kirim ulang sekali
          if(xhr.status === 419 && !retried){
            $.getJSON(@json(route('login.token')), function(res){
              setToken(res.token);
              form.trigger("submit", [true]);
            }).fail(function(){
              toastr.error("Sesi berakhir, silakan muat ulang halaman.");
            });
            return;
          }
          toastr.error(xhr.status === 429 ? "Terlalu banyak percobaan, coba lagi nanti." : "Terjadi kesalahan, silakan coba lagi.");
        }
      })
      return false;
    })

    $("#lokasiSearch").on("input", function () {
      var keyword = $(this).val().toLowerCase().trim();
      var visibleCount = 0;

      $("#lokasiGrid .lokasi-item").each(function () {
        var lokasiName = $(this).data("lokasi");
        var isMatch = lokasiName.indexOf(keyword) !== -1;
        $(this).toggle(isMatch);
        if (isMatch) {
          visibleCount++;
        }
      });

      $("#lokasiNoResult").toggleClass("d-none", visibleCount > 0 || keyword === "");
    });

      $("#lokasiGrid").on("click", ".lokasi-item", function () {
        var lokasiName = $(this).data("lokasi-name");
        $("#lokasiGrid .lokasi-item").removeClass("active").attr("aria-pressed", "false");
        $(this).addClass("active").attr("aria-pressed", "true");
        $("#selectedLokasiName").text(lokasiName);
        $("#selectedLokasiLabel").removeClass("d-none");
        $("#lokasi").val(lokasiName);
      });

      $("#lokasiGrid").on("keydown", ".lokasi-item", function (e) {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          $(this).trigger("click");
        }
      });

      $("#authMobileToggle").on("click", function () {
        var $inner = $(".authentication-inner");
        $inner.toggleClass("show-lokasi");
        var showingLokasi = $inner.hasClass("show-lokasi");
        $("#authMobileToggleText").text(showingLokasi ? "Kembali ke Formulir Masuk" : "Lihat Lokasi Program");
        $(this).find("i").toggleClass("ri-map-pin-2-line ri-arrow-left-line");
      });
    
})
</script>