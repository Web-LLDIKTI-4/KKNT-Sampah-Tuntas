{{-- Captcha aktif hanya bila backend juga bisa memverifikasi (site + secret key) --}}
@php($recaptchaSiteKey = app(\App\Services\RecaptchaService::class)->siteKey())
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
    <link rel="stylesheet" href="{{ asset('css/laporan.css') }}?v={{ filemtime(public_path('css/laporan.css')) }}" />

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
          font-size: clamp(30px, 3vw, 46px);
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
          display: block;
          text-align: center;
          line-height: 1.3;
          text-shadow: 0 1px 3px rgba(0,0,0,0.4);
        }

        /* Ikon inline di dalam teks agar nama 2 baris tetap simetris */
        .lokasi-item-badge i {
          font-size: 0.9rem;
          vertical-align: -0.1em;
          margin-right: 2px;
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
          text-align: center;
          flex: 1;
          min-width: 0;
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
            font-size: clamp(26px, 5vw, 40px);
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

        /* Desktop kecil: panel kiri sempit, 2 kolom agar statistik tidak berdesakan */
        @media (min-width: 992px) and (max-width: 1199px) {
          .lokasi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
          }
        }

        /* HP: 1 kolom agar 3 statistik card tidak terpotong */
        @media (max-width: 575px) {
          .lokasi-grid {
            grid-template-columns: minmax(0, 1fr);
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

        /* Toggle Beranda / Capaian Program di panel kiri */
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

        /* Header boleh wrap agar tabel 3 kolom muat di HP */
        .laporan-card .table th {
          white-space: normal;
        }

        /* Tabel PTS 8 kolom: padding dirapatkan agar muat di desktop */
        .laporan-card .table-pts > :not(caption) > * > * {
          padding-left: 0.75rem;
          padding-right: 0.75rem;
        }

        @media (max-width: 575px) {
          .laporan-card .table > :not(caption) > * > * {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
          }
        }

        /* Toolbar Capaian: select & tombol satu baris, tinggi seragam; turun baris rapi di layar kecil */
        .capaian-toolbar .capaian-field {
          flex: 1 1 140px;
          max-width: 220px;
        }

        .capaian-toolbar .form-select,
        .capaian-toolbar .capaian-action {
          height: auto;
          min-height: 2.5rem;
        }

        /* Netralkan .btn-primary global (tombol Masuk) agar Unduh PNG = Reset */
        .capaian-toolbar .capaian-action,
        .capaian-toolbar .capaian-action:hover {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          flex: 0 0 auto;
          width: auto;
          min-width: 9rem;
          padding: 0.375rem 1rem;
          border-radius: 0.375rem;
          box-shadow: none;
          transform: none;
        }

        @media (max-width: 575px) {
          .capaian-toolbar .capaian-field {
            max-width: none;
          }

          .capaian-toolbar .capaian-action,
          .capaian-toolbar .capaian-action:hover {
            flex: 1 1 100%;
          }
        }

        /* Tab Panduan: daftar dokumen publik */
        .panduan-toolbar {
          display: flex;
          align-items: center;
          gap: 0.75rem 1rem;
          flex-wrap: wrap;
          margin-bottom: 1rem;
        }

        .panduan-search {
          flex: 1 1 260px;
          max-width: 28rem;
        }

        .panduan-count {
          color: rgba(255, 255, 255, 0.9);
          font-size: 0.9rem;
          font-weight: 600;
          font-variant-numeric: tabular-nums;
        }

        .panduan-card {
          overflow: hidden;
        }

        .panduan-list {
          list-style: none;
          margin: 0;
          padding: 0;
          max-height: calc(100vh - 300px);
          /* min-height: 16rem; */
          overflow-y: auto;
          overscroll-behavior: contain;
        }

        .panduan-item {
          display: grid;
          grid-template-columns: auto minmax(0, 1fr) auto;
          align-items: center;
          gap: 0.35rem 1rem;
          padding: 1rem 1.25rem;
          border-bottom: 1px solid #ecebf2;
        }

        .panduan-item:last-child {
          border-bottom: 0;
        }

        .panduan-icon {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 2.75rem;
          height: 2.75rem;
          border-radius: 12px;
          font-size: 1.35rem;
        }

        .panduan-icon--pdf { background: #fbecec; color: #b4413b; }
        .panduan-icon--word { background: #e9effb; color: #2f5fb3; }
        .panduan-icon--excel { background: #e8f4ee; color: #23784d; }
        .panduan-icon--ppt { background: #fbefe6; color: #b0561f; }
        .panduan-icon--other { background: #eeeff6; color: #4f5fd8; }

        .panduan-judul {
          margin: 0;
          font-size: 0.975rem;
          font-weight: 600;
          line-height: 1.4;
          color: #2e2b3f;
          overflow-wrap: anywhere;
        }

        .panduan-deskripsi {
          margin: 0.2rem 0 0;
          font-size: 0.85rem;
          line-height: 1.5;
          color: #5d596c;
          display: -webkit-box;
          -webkit-line-clamp: 2;
          -webkit-box-orient: vertical;
          overflow: hidden;
        }

        .panduan-meta {
          margin: 0.3rem 0 0;
          font-size: 0.78rem;
          color: #6d6b77;
        }

        .panduan-unduh {
          display: inline-flex;
          align-items: center;
          gap: 0.35rem;
          width: auto;
          white-space: nowrap;
        }

        .panduan-unduh:active {
          transform: translateY(1px);
        }

        .panduan-empty {
          display: flex;
          flex-direction: column;
          align-items: center;
          gap: 0.5rem;
          padding: 3rem 1.5rem;
          color: #6d6b77;
          text-align: center;
        }

        .panduan-empty i {
          font-size: 2rem;
          color: #8b8ea8;
        }

        @media (max-width: 575px) {
          /* tombol unduh pindah ke bawah teks agar judul tidak terjepit */
          .panduan-item {
            grid-template-columns: auto minmax(0, 1fr);
            align-items: start;
            padding: 0.9rem 1rem;
          }

          .panduan-unduh {
            grid-column: 2;
            justify-self: start;
            margin-top: 0.35rem;
          }

          .panduan-list {
            max-height: none;
            min-height: 0;
          }
        }

        @media (max-width: 420px) {
          /* 3 pill muat di layar 360px: ikon disembunyikan, padding dirapatkan */
          .panel-switch {
            display: flex;
            max-width: 100%;
          }

          .panel-switch .nav-link {
            padding: 0.5rem 0.8rem;
            font-size: 0.85rem;
            white-space: nowrap;
          }

          .panel-switch .nav-link i {
            display: none;
          }
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

        /* Badge reCAPTCHA di atas panel auth (z-index 1) dan toggle mobile (1050) */
        .grecaptcha-badge {
          z-index: 1060;
        }
      </style>


  </head>

  <body>
    <!-- Content -->

    <div class="authentication-wrapper authentication-cover">
      <!-- Mobile switch button -->
      <button type="button" id="authMobileToggle" class="auth-mobile-toggle d-lg-none">
        <i class="ri-information-line"></i>
        <span id="authMobileToggleText">Lihat Info &amp; Dokumen</span>
      </button>
      <!-- /Logo -->
      <div class="authentication-inner row m-0">
        <!-- Left Section -->
        <div class="auth-left-panel d-lg-flex col-lg-7 col-xl-8 p-0">
          <div class="lokasi-panel">
            <div class="panel-switch-wrap">
              <div class="nav panel-switch" role="tablist" aria-label="Tampilan panel">
                <button type="button" class="nav-link active" id="tabHome" data-bs-toggle="pill" data-bs-target="#paneHome" role="tab" aria-controls="paneHome" aria-selected="true">
                  <i class="ri-home-4-line"></i> Beranda
                </button>
                <button type="button" class="nav-link" id="tabLaporan" data-bs-toggle="pill" data-bs-target="#paneLaporan" role="tab" aria-controls="paneLaporan" aria-selected="false">
                  <i class="ri-file-chart-line"></i> Capaian Program
                </button>
                <button type="button" class="nav-link" id="tabPanduan" data-bs-toggle="pill" data-bs-target="#panePanduan" role="tab" aria-controls="panePanduan" aria-selected="false">
                  <i class="ri-book-open-line"></i> Dokumen
                </button>
              </div>
            </div>

            <div class="tab-content p-0 bg-transparent shadow-none">
            <div class="tab-pane fade show active" id="paneHome" role="tabpanel" aria-labelledby="tabHome">
            <div class="lokasi-panel-header">
              <img src="../assets/images/sampah.png" alt="Kegiatan KKN" class="lokasi-hero-img" />
              <h2 class="lokasi-panel-title">Program GRADASI : KKN Tematik <br /> Sampah Tuntas LLDIKTI Wilayah IV</h2>

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

            <div class="tab-pane fade" id="paneLaporan" role="tabpanel" aria-labelledby="tabLaporan">
              <div class="laporan-wrap">
                <h2 class="laporan-title">Capaian Program</h2>
                <p class="lokasi-panel-subtitle mb-4">Persentase Pengurangan Sampah per Kota/Kabupaten dengan sebaran Kecamatan, Kelurahan/Desa yang menjadi lokus pada KKN Tematik Sampah Tuntas.<br /><em>klik nama kecamatan, kelurahan untuk melihat capaian, kinerja perguruan tinggi maupun permasalahan di lapangan.</em></p>

                <div class="card laporan-card">
                  <div class="card-body">
                    <div data-drilldown="{{ route('login.laporan') }}" aria-live="polite">
                      @include('laporan._capaian_publik', $laporan)
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="panePanduan" role="tabpanel" aria-labelledby="tabPanduan">
              <div class="laporan-wrap">
                <h2 class="laporan-title">Dokumen</h2>
                <p class="lokasi-panel-subtitle mb-4">Dokumen KKN Tematik Sampah Tuntas yang bisa diunduh.</p>
                @include('panduan._publik', ['panduanList' => $panduanList ?? collect()])
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
              @if($recaptchaSiteKey)
                <input type="hidden" name="g-recaptcha-response" value="" />
              @endif
              <div class="form-floating form-floating-outline mb-5">
                <input
                  type="text"
                  class="form-control"
                  id="email"
                  name="username"
                  placeholder="Masukkan username"
                  autofocus />
                <label for="email">Username</label>
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
    <script src="{{ asset('js/drilldown.js') }}?v={{ filemtime(public_path('js/drilldown.js')) }}"></script>
    @if($recaptchaSiteKey)
      <script src="https://www.google.com/recaptcha/api.js?render={{ urlencode($recaptchaSiteKey) }}" async defer></script>
    @endif
  </body>
</html>
<script>
$(function(){

    // Mobile: panel kiri (Beranda/Capaian/Dokumen) dan form login bergantian tampil
    function setMobilePanelVisible(visible) {
      $(".authentication-inner").toggleClass("show-lokasi", visible);
      $("#authMobileToggleText").text(visible ? "Kembali ke Formulir Masuk" : "Lihat Info & Dokumen");
      $("#authMobileToggle i").toggleClass("ri-arrow-left-line", visible).toggleClass("ri-information-line", !visible);
    }

    // Token CSRF diperbarui dari respons server, tanpa reload halaman
    function setToken(token) {
      if (!token) return;
      $("#formAuthentication input[name='_token']").val(token);
      $('meta[name="csrf-token"]').attr("content", token);
    }

    var recaptchaSiteKey = @json($recaptchaSiteKey);

    $("#formAuthentication").on("submit",function(e, retried){
      var form = $(this);
      var btn = $("#btnSubmit_"+form.attr("id"));
      // Cegah double submit selama token/AJAX berjalan
      if(btn.prop("disabled")) return false;
      if(!recaptchaSiteKey){
        sendLogin(form, retried);
        return false;
      }
      if(!window.grecaptcha){
        toastr.error("Verifikasi keamanan gagal dimuat. Periksa koneksi/adblock lalu muat ulang halaman.");
        return false;
      }
      btn.prop("disabled",true);
      // Token diambil saat submit (berlaku 2 menit, sekali pakai)
      grecaptcha.ready(function(){
        grecaptcha.execute(recaptchaSiteKey, {action: 'login'}).then(function(token){
          form.find("input[name='g-recaptcha-response']").val(token);
          btn.prop("disabled",false);
          sendLogin(form, retried);
        }, function(){
          btn.prop("disabled",false);
          toastr.error("Verifikasi keamanan gagal, silakan coba lagi.");
        });
      });
      return false;
    })

    function sendLogin(form, retried){
      var action = form.attr("action");
      var id = form.attr("id");
      var btnHtml = $("#btnSubmit_"+id+"").html();
      var dString = form.serialize();
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
              // Grid lokasi ada di tab Home; pindah dulu bila user sedang membuka tab lain
              bootstrap.Tab.getOrCreateInstance(document.getElementById("tabHome")).show();
              setMobilePanelVisible(true);
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
    }

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
        setMobilePanelVisible(!$(".authentication-inner").hasClass("show-lokasi"));
      });

      $("#panduanSearch").on("input", function () {
        var keyword = $(this).val().toLowerCase().trim();
        var $items = $("#panduanList .panduan-item");
        var visibleCount = 0;

        $items.each(function () {
          var isMatch = String($(this).data("search")).indexOf(keyword) !== -1;
          $(this).toggleClass("d-none", !isMatch);
          if (isMatch) {
            visibleCount++;
          }
        });

        var total = $("#panduanCount").data("total");
        $("#panduanCount").text(keyword === "" ? total + " dokumen" : visibleCount + " dari " + total + " dokumen");
        $("#panduanList").toggleClass("d-none", visibleCount === 0);
        $("#panduanNoResult").toggleClass("d-none", visibleCount > 0);
      });

      // Link #panduan bisa dibagikan untuk langsung membuka tab Panduan
      if (window.location.hash === "#panduan") {
        bootstrap.Tab.getOrCreateInstance(document.getElementById("tabPanduan")).show();
        setMobilePanelVisible(true);
      }
    
})
</script>