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
    <link rel="stylesheet" href="../../assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/leaflet/leaflet.css') }}" />

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
          margin-bottom: 0;
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
          /* top 0.5rem: sisa ruang untuk ring .active & hover lift */
          padding: 0.5rem 1.5rem 1.5rem;
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

      /* Tombol Detail tabel capaian: selalu biru solid */
      .btn-icon.btn-primary {
          color: #fff;
          background: #667eea;
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

        /* Satu baris; kalau panel sempit jadi scroll horizontal (scrollbar disembunyikan) */
        .panel-switch {
          display: inline-flex;
          flex-wrap: nowrap;
          max-width: 100%;
          overflow-x: auto;
          scroll-snap-type: x proximity;
          scrollbar-width: none;
          gap: 0.25rem;
          padding: 0.3rem;
          background: rgba(255, 255, 255, 0.16);
          border: 1px solid rgba(255, 255, 255, 0.3);
          border-radius: 999px;
        }

        .panel-switch::-webkit-scrollbar {
          display: none;
        }

        .panel-switch .nav-link {
          display: inline-flex;
          flex: 0 0 auto;
          align-items: center;
          justify-content: center;
          white-space: nowrap;
          scroll-snap-align: start;
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

        /* Tab Peta Sebaran */
        .peta-head {
          display: flex;
          flex-wrap: wrap;
          align-items: center;
          justify-content: space-between;
          gap: 1rem;
          margin-bottom: 1.25rem;
        }

        .peta-head-title {
          display: flex;
          align-items: center;
          gap: 0.75rem;
        }

        .peta-head-icon {
          width: 44px;
          height: 44px;
          flex-shrink: 0;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          border-radius: 12px;
          background: rgba(var(--bs-primary-rgb), 0.12);
          color: var(--bs-primary);
          font-size: 1.4rem;
        }

        .peta-title {
          margin: 0;
          color: var(--bs-primary);
          font-weight: 800;
          font-size: 1.25rem;
        }

        .peta-subtitle,
        .peta-muted {
          font-size: 0.8125rem;
          color: var(--bs-secondary-color);
        }

        .peta-filters {
          display: flex;
          flex-wrap: wrap;
          align-items: center;
          gap: 0.5rem;
        }

        .peta-filters .form-select {
          width: auto;
          min-width: 7.5rem;
        }

        #petaPt {
          max-width: 16rem;
          text-overflow: ellipsis;
        }

        /* Select2 (search-select.js) di filter peta: ringkas setinggi btn-sm */
        .peta-filters > .select2-container {
          width: 8rem !important;
        }

        .peta-filters > #petaPt + .select2-container {
          width: 16rem !important;
        }

        #panePeta .select2-container--default .select2-selection--single,
        #panePeta .select2-container--default .select2-selection--single .select2-selection__arrow {
          height: 2.125rem;
        }

        #panePeta .select2-container--default .select2-selection--single .select2-selection__rendered {
          line-height: 2rem;
          font-size: 0.8125rem;
          padding-left: 0.75rem;
        }

        #panePeta .select2-results__option {
          font-size: 0.8125rem;
        }

        .peta-map-wrap {
          position: relative;
        }

        .peta-sebaran {
          height: 600px;
          border-radius: 14px;
          z-index: 0;
          transition: opacity 0.2s;
        }

        .peta-map-wrap[aria-busy="true"] .peta-sebaran {
          opacity: 0.55;
        }

        /* Overlay status (memuat / kosong / gagal) di atas peta */
        .peta-empty {
          position: absolute;
          inset: 0;
          z-index: 500;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          gap: 0.75rem;
          padding: 2.5rem 1rem;
          border-radius: 14px;
          background: rgba(255, 255, 255, 0.9);
          text-align: center;
        }

        .peta-side {
          display: flex;
          flex-direction: column;
          gap: 1rem;
        }

        .peta-summary {
          padding: 1rem 1.25rem;
          border-radius: 14px;
          background: rgba(var(--bs-primary-rgb), 0.08);
        }

        .peta-summary-value {
          margin: 0.35rem 0 0.15rem;
          color: var(--bs-primary);
          font-weight: 800;
          font-size: 2.25rem;
          line-height: 1.1;
        }

        .peta-top {
          padding: 0.875rem;
          border: 1px solid var(--bs-border-color);
          border-radius: 14px;
        }

        .peta-top-head {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 0.5rem;
          margin-bottom: 0.625rem;
          font-weight: 700;
        }

        .peta-top-list {
          display: flex;
          flex-direction: column;
          gap: 0.5rem;
          max-height: 300px;
          overflow-y: auto;
        }

        .peta-top-item {
          width: 100%;
          display: flex;
          align-items: center;
          gap: 0.75rem;
          padding: 0.625rem 0.75rem;
          border: 1px solid var(--bs-border-color);
          border-radius: 10px;
          background: #fff;
          text-align: left;
          transition: border-color 0.15s, box-shadow 0.15s;
        }

        .peta-top-item:hover,
        .peta-top-item:focus-visible {
          border-color: var(--bs-primary);
          box-shadow: 0 4px 12px rgba(20, 24, 70, 0.1);
          outline: 0;
        }

        .peta-rank {
          width: 2.25rem;
          height: 2.25rem;
          flex-shrink: 0;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          border-radius: 50%;
          background: #eef0f4;
          color: #4b4f5c;
          font-weight: 700;
          font-size: 0.8125rem;
        }

        .peta-rank-1 { background: #f6c343; color: #5c4300; }
        .peta-rank-2 { background: #d3d8e0; color: #3c4250; }
        .peta-rank-3 { background: #e7ad7e; color: #4a2a10; }

        .peta-top-body {
          flex: 1;
          min-width: 0;
        }

        .peta-top-name {
          display: block;
          overflow: hidden;
          white-space: nowrap;
          text-overflow: ellipsis;
          color: var(--bs-heading-color);
          font-weight: 600;
        }

        .peta-top-count {
          color: var(--bs-primary);
          font-weight: 800;
          font-size: 1.125rem;
        }

        .peta-top-reduksi {
          display: block;
          margin-top: 0.25rem;
          color: var(--bs-secondary-color);
          font-size: 0.75rem;
        }

        .peta-kec-list {
          display: flex;
          flex-direction: column;
          gap: 0.625rem;
          max-height: 260px;
          overflow-y: auto;
        }

        .peta-kec-row {
          display: flex;
          justify-content: space-between;
          gap: 0.5rem;
          font-size: 0.8125rem;
        }

        .peta-kec-name {
          min-width: 0;
          overflow: hidden;
          white-space: nowrap;
          text-overflow: ellipsis;
          color: var(--bs-heading-color);
          font-weight: 600;
        }

        .peta-kec-value {
          flex-shrink: 0;
          font-weight: 700;
        }

        .peta-kec-value.is-empty {
          color: var(--bs-secondary-color);
          font-weight: 400;
        }

        .peta-kec-bar {
          height: 6px;
          margin-top: 0.25rem;
          border-radius: 999px;
          background: #eef0f4;
          overflow: hidden;
        }

        .peta-kec-fill {
          height: 100%;
          border-radius: inherit;
        }

        .peta-kec-fill.is-high { background: #43a047; }
        .peta-kec-fill.is-mid { background: #f6c343; }
        .peta-kec-fill.is-low { background: #e53935; }

        .peta-tip {
          display: flex;
          gap: 0.5rem;
          padding: 0.75rem 0.875rem;
          border-radius: 10px;
          background: #e8f3ff;
          color: #1a4f8a;
          font-size: 0.8125rem;
        }

        .peta-legend {
          padding: 0.5rem 0.625rem;
          border-radius: 8px;
          background: #fff;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
          font-size: 0.75rem;
          line-height: 1.5;
        }

        .peta-legend-row {
          display: flex;
          align-items: center;
          gap: 0.375rem;
        }

        .peta-legend-swatch {
          width: 22px;
          display: inline-flex;
          justify-content: center;
        }

        .peta-legend-dot {
          display: inline-block;
          border-radius: 50%;
          box-sizing: border-box;
        }

        /* Heartbeat lembut bubble peta: detak ganda lalu diam */
        @keyframes petaHeartbeat {
          0%, 45%, 100% { transform: scale(1); }
          12% { transform: scale(1.05); }
          24% { transform: scale(1); }
          34% { transform: scale(1.03); }
        }

        /* Bubble peta: chip lembut, gradien dari defs SVG bersama */
        .peta-dot {
          cursor: pointer;
          transform-box: fill-box;
          transform-origin: center;
          transition: transform 0.2s ease, fill-opacity 0.2s ease, stroke 0.2s ease, stroke-width 0.2s ease;
        }

        .peta-bubble {
          animation: petaHeartbeat 3s ease-in-out infinite;
        }

        .peta-dot:hover,
        .peta-dot.is-active {
          animation: none;
          transform: scale(1.1);
          fill-opacity: 0.95;
          stroke-width: 2.5px;
        }

        /* Ring aksen + halo putih agar beda dari border gelap */
        .peta-dot.is-active {
          stroke: #667eea;
          stroke-width: 3.5px;
          stroke-opacity: 1;
          filter: drop-shadow(0 0 1.5px #fff) drop-shadow(0 0 1px #fff);
        }

        @media (prefers-reduced-motion: reduce) {
          .peta-sebaran,
          .peta-top-item {
            transition: none;
          }

          .peta-bubble {
            animation: none;
          }

          .peta-dot {
            transition: none;
          }

          .peta-dot:hover,
          .peta-dot.is-active {
            transform: none;
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

        /* Desktop kecil: panel kiri sempit, pill dirapatkan */
        @media (min-width: 992px) and (max-width: 1199px) {
          .panel-switch .nav-link {
            padding: 0.5rem 0.8rem;
            font-size: 0.875rem;
          }
        }

        /* HP: 4 pill jadi grid 2x2 agar semua terlihat tanpa scroll */
        @media (max-width: 575px) {
          .panel-switch {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            overflow: visible;
            border-radius: 1.4rem;
          }

          .panel-switch .nav-link {
            min-width: 0;
          }
        }

        @media (max-width: 420px) {
          /* layar ≤420px: ikon disembunyikan, padding dirapatkan */
          .panel-switch .nav-link {
            padding: 0.5rem 0.8rem;
            font-size: 0.85rem;
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

          .peta-sebaran {
            height: 380px;
          }

          .peta-head,
          .peta-filters {
            flex-direction: column;
            align-items: stretch;
          }

          .peta-filters .form-select {
            width: 100%;
            max-width: none;
          }

          .peta-filters > .select2-container {
            width: 100% !important;
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
                <button type="button" class="nav-link" id="tabPeta" data-bs-toggle="pill" data-bs-target="#panePeta" role="tab" aria-controls="panePeta" aria-selected="false" title="Peta Sebaran Mahasiswa">
                  <i class="ri-map-pin-line"></i> <span>Peta<span class="d-none d-lg-inline"> Sebaran</span><span class="d-none d-xl-inline"> Mahasiswa</span></span>
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
                Kegiatan ini melibatkan <b>{{ $jumlahMahasiswa }} Mahasiswa</b>  dan <b>{{ $jumlahDpl }} DPL</b> dari <b>{{ $jumlahPt }} Perguruan Tinggi.</b> <br />
                Lokus kegiatan disebar ke {{ $jumlahKecamatan }} Kecamatan dan {{ $jumlahKelurahan }} Kelurahan/Desa.
              </p>
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
                            <span class="lokasi-stat-label">PTS</span>
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

            <div class="tab-pane fade" id="panePeta" role="tabpanel" aria-labelledby="tabPeta">
              <div class="laporan-wrap">
                <div class="card laporan-card">
                  <div class="card-body">
                    <div class="peta-head">
                      <div class="peta-head-title">
                        <span class="peta-head-icon" aria-hidden="true"><i class="ri-map-2-line"></i></span>
                        <div>
                          <h2 class="peta-title">Peta Sebaran Mahasiswa</h2>
                          <p id="petaSubtitle" class="peta-subtitle mb-0">Memuat data…</p>
                        </div>
                      </div>
                      <div class="peta-filters" role="group" aria-label="Filter peta">
                        <select id="petaTahun" class="form-select form-select-sm" aria-label="Tahun"></select>
                        <select id="petaPt" class="form-select form-select-sm" aria-label="Perguruan tinggi"></select>
                        <button type="button" id="petaReset" class="btn btn-sm btn-outline-secondary">
                          <i class="ri-refresh-line me-1"></i>Reset
                        </button>
                      </div>
                    </div>

                    <div class="row g-4">
                      <div class="col-lg-8">
                        <div id="petaMapWrap" class="peta-map-wrap" aria-busy="false">
                          <div id="petaSebaran" class="peta-sebaran" data-url="{{ route('login.peta') }}" data-filter-url="{{ route('login.peta.filter') }}" role="region" aria-label="Peta sebaran mahasiswa"></div>
                          <div id="petaStatus" class="peta-empty d-none" aria-live="polite">
                            <p id="petaStatusText" class="text-muted mb-0"></p>
                            <button type="button" id="petaRetry" class="btn btn-sm btn-primary d-none">Coba lagi</button>
                          </div>
                        </div>
                      </div>

                      <div class="col-lg-4">
                        <div class="peta-side">
                          <div class="peta-summary" aria-live="polite">
                            <span id="petaSummaryBadge" class="badge bg-primary"></span>
                            <div id="petaTotal" class="peta-summary-value">–</div>
                            <div id="petaSummaryNote" class="peta-muted"></div>
                          </div>

                          <div>
                            <label for="petaSorot" class="form-label fw-semibold mb-1">Sorot Desa di Peta:</label>
                            <select id="petaSorot" class="form-select form-select-sm">
                              <option value="">-- Pilih Desa untuk Fokus Peta --</option>
                            </select>
                          </div>

                          <div class="peta-top">
                            <div class="peta-top-head">
                              <span><i class="ri-trophy-line text-warning me-1" aria-hidden="true"></i>Top 5 Desa · Pengurangan Sampah Tertinggi</span>
                              <small class="peta-muted fw-normal">Klik untuk sorot</small>
                            </div>
                            <ol id="petaTop" class="peta-top-list list-unstyled mb-0"></ol>
                            <p id="petaTopEmpty" class="peta-muted mb-0 d-none">Belum ada data untuk filter ini.</p>
                          </div>
                        </div>
                      </div>
                        <div class="peta-top w-100">
                            <div class="peta-top-head">
                                <span><i class="ri-recycle-line text-success me-1" aria-hidden="true"></i>Pengurangan Sampah per Kecamatan</span>
                                <small id="petaKecPeriode" class="peta-muted fw-normal"></small>
                            </div>
                            <ul id="petaKec" class="peta-kec-list list-unstyled mb-0"></ul>
                            <p id="petaKecEmpty" class="peta-muted mb-0 d-none">Belum ada data pengurangan sampah.</p>
                        </div>

                        <div class="peta-tip">
                            <i class="ri-lightbulb-line" aria-hidden="true"></i>
                            <span><strong>Tips Interaktif:</strong> Klik lingkaran desa pada peta untuk melihat detail.</span>
                        </div>
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
                <p class="mb-0 text-muted">
                    Sebelum login atau masuk ke dalam dashboard, pilih lokasi kegiatan terlebih dahulu
                </p>
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
    <script src="../../assets/vendor/libs/select2/select2.js"></script>
    <script src="{{ asset('js/search-select.js') }}?v={{ filemtime(public_path('js/search-select.js')) }}"></script>
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

      // Peta sebaran: Leaflet dimuat saat tab pertama dibuka; respons di-cache per kombinasi filter
      var petaMap = null;
      var petaLayer = null;
      var petaMarkers = [];
      var petaOptions = null;
      var petaFilter = { tahun: null, kodept: null };
      var petaCache = {};
      var petaSeq = 0;
      var petaTimer = null;
      var petaBooting = false;
      var petaFitted = false;
      var petaBounds = null;
      // Listener moveend milik petaFocus yang belum jalan
      var petaFocusPending = null;
      var petaReduceMotion = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
      // Index = tier dari server (0 = tanpa mahasiswa)
      // color = tepi gradien, light = pusat, stroke = border (versi gelap)
      var petaTiers = [
        { color: "#b8bfc8", light: "#d9dee3", stroke: "#8a939d", radius: 5 },
        { color: "#4fb286", light: "#93d6b6", stroke: "#2f8a61", radius: 7 },
        { color: "#4aaed6", light: "#90d0ec", stroke: "#2a86ad", radius: 10 },
        { color: "#5b7fe0", light: "#9ab3f2", stroke: "#3a5cc4", radius: 13 },
        { color: "#9b74d4", light: "#c6a9ec", stroke: "#7650b5", radius: 16 },
        { color: "#e8706b", light: "#f4a6a1", stroke: "#c94a45", radius: 20 }
      ];
      var petaLegendItems = [[1, "< 6"], [2, "6–10"], [3, "11–20"], [4, "21–30"], [5, "> 30"]];

      function petaEl(tag, className, text) {
        var el = document.createElement(tag);
        if (className) el.className = className;
        if (text !== undefined && text !== null) el.textContent = text;
        return el;
      }

      function petaStatus(text, retry) {
        $("#petaStatusText").text(text || "");
        $("#petaRetry").toggleClass("d-none", !retry);
        $("#petaStatus").toggleClass("d-none", !text);
      }

      function petaList(value) {
        return Array.isArray(value) ? value : [];
      }

      // null/non-numerik -> null; selain itu angka berhingga
      function petaPersen(value) {
        if (value === null || value === undefined || value === "") return null;
        var num = Number(value);
        return Number.isFinite(num) ? num : null;
      }

      function petaPersenText(value) {
        return value.toLocaleString("id-ID", { maximumFractionDigits: 2 }) + "%";
      }

      function petaReduksiText(row, periode) {
        var persen = petaPersen(row.persen_pengurangan);
        if (persen === null) return "Pengurangan sampah: Belum ada data";
        var label = periode && periode.label ? " (" + periode.label + ")" : "";
        return "Pengurangan sampah: " + petaPersenText(persen) + label;
      }

      function petaPopup(row, periode) {
        var wrap = petaEl("div");
        wrap.appendChild(petaEl("strong", null, row.desa));
        if (row.kecamatan) wrap.appendChild(petaEl("div", "peta-muted", "Kec. " + row.kecamatan));
        wrap.appendChild(petaEl("div", null, (row.jumlah_label || "0") + " mahasiswa"));
        wrap.appendChild(petaEl("div", "peta-muted", petaReduksiText(row, periode)));
        return wrap;
      }

      function petaLoadLeaflet(done, fail) {
        if (window.L) {
          done();
          return;
        }
        var script = document.createElement("script");
        script.src = "{{ asset('assets/vendor/libs/leaflet/leaflet.js') }}";
        script.onload = done;
        script.onerror = function () {
          script.remove();
          fail();
        };
        document.body.appendChild(script);
      }

      function petaInitMap() {
        // Pusat awal Jawa Barat; segera diganti fitBounds bila ada data
        petaMap = L.map("petaSebaran", { scrollWheelZoom: false }).setView([-6.92, 107.6], 9);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
          maxZoom: 19,
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
        }).addTo(petaMap);
        petaLayer = L.featureGroup().addTo(petaMap);

        var legend = L.control({ position: "bottomleft" });
        legend.onAdd = function () {
          var box = petaEl("div", "peta-legend");
          box.appendChild(petaEl("div", "fw-bold mb-1", "Densitas Mahasiswa"));
          petaLegendItems.forEach(function (item) {
            var tier = petaTiers[item[0]];
            var row = petaEl("div", "peta-legend-row");
            var swatch = petaEl("span", "peta-legend-swatch");
            var dot = petaEl("span", "peta-legend-dot");
            dot.style.width = dot.style.height = tier.radius + "px";
            dot.style.background = "radial-gradient(circle at 35% 30%, " + tier.light + ", " + tier.color + " 70%)";
            dot.style.border = "1.5px " + (item[0] > 0 ? "solid " : "dashed ") + tier.stroke;
            swatch.appendChild(dot);
            row.appendChild(swatch);
            row.appendChild(petaEl("span", null, item[1]));
            box.appendChild(row);
          });
          L.DomEvent.disableClickPropagation(box);
          return box;
        };
        legend.addTo(petaMap);
        petaGradients();
      }

      // Satu set radialGradient dipakai semua bubble (tanpa filter per path)
      function petaGradients() {
        if (document.getElementById("petaGrad0")) return;
        var ns = "http://www.w3.org/2000/svg";
        var svg = document.createElementNS(ns, "svg");
        var defs = document.createElementNS(ns, "defs");
        svg.setAttribute("width", "0");
        svg.setAttribute("height", "0");
        svg.setAttribute("aria-hidden", "true");
        svg.style.position = "absolute";
        petaTiers.forEach(function (tier, i) {
          var grad = document.createElementNS(ns, "radialGradient");
          grad.setAttribute("id", "petaGrad" + i);
          grad.setAttribute("cx", "35%");
          grad.setAttribute("cy", "30%");
          grad.setAttribute("r", "75%");
          // Stop tengah 70%: area pucat dipersempit agar isi lebih pekat
          [["0%", tier.light], ["70%", tier.color], ["100%", tier.color]].forEach(function (s) {
            var stop = document.createElementNS(ns, "stop");
            stop.setAttribute("offset", s[0]);
            stop.setAttribute("stop-color", s[1]);
            grad.appendChild(stop);
          });
          defs.appendChild(grad);
        });
        svg.appendChild(defs);
        document.getElementById("petaSebaran").appendChild(svg);
      }

      function petaBuildFilters() {
        var tahun = document.getElementById("petaTahun");
        var pt = document.getElementById("petaPt");

        tahun.length = 0;
        petaList(petaOptions.tahun).forEach(function (year) {
          tahun.add(new Option(String(year), String(year)));
        });

        pt.length = 0;
        pt.add(new Option("Semua PT", ""));
        petaList(petaOptions.pt).forEach(function (item) {
          pt.add(new Option(item.nama, String(item.kodept)));
        });
      }

      function petaSyncControls() {
        $("#petaTahun").val(petaFilter.tahun === null ? "" : String(petaFilter.tahun));
        $("#petaPt").val(petaFilter.kodept || "");
        // Segarkan tampilan Select2 tanpa memicu handler change
        $("#petaTahun, #petaPt").trigger("change.select2");
      }

      function petaParams() {
        var params = {};
        if (petaFilter.tahun !== null) params.tahun = petaFilter.tahun;
        if (petaFilter.kodept) params.kodept = petaFilter.kodept;
        return params;
      }

      function petaSchedule() {
        petaSyncControls();
        clearTimeout(petaTimer);
        petaTimer = setTimeout(petaFetch, 250);
      }

      // Kosongkan marker & ringkasan agar data filter lama tidak tampil saat gagal
      function petaClear(message) {
        petaFocusCancel();
        petaMap.closePopup();
        petaLayer.clearLayers();
        petaMarkers = [];
        document.getElementById("petaSorot").length = 1;
        $("#petaSorot").trigger("change.select2");
        document.getElementById("petaTop").textContent = "";
        $("#petaTopEmpty").addClass("d-none");
        document.getElementById("petaKec").textContent = "";
        $("#petaKecPeriode").text("");
        $("#petaKecEmpty").addClass("d-none");
        $("#petaSubtitle").text("Data tidak tersedia");
        $("#petaTotal").text("–");
        $("#petaSummaryNote").text("");
        petaStatus(message, true);
      }

      // Nilai filter yang tidak lagi ada di options dikembalikan ke default
      function petaSanitize() {
        var years = petaList(petaOptions.tahun).map(function (y) { return parseInt(y, 10); });
        var ptIds = petaList(petaOptions.pt).map(function (p) { return String(p.kodept); });
        if (petaFilter.tahun !== null && years.indexOf(petaFilter.tahun) === -1) {
          petaFilter.tahun = parseInt(petaOptions.tahun_default, 10) || years[0] || null;
        }
        if (petaFilter.kodept && ptIds.indexOf(petaFilter.kodept) === -1) petaFilter.kodept = null;
      }

      // 422 = filter basi: muat ulang options sekali (tanpa cache), lalu fetch ulang
      function petaRefreshOptions(seq) {
        $("#petaMapWrap").attr("aria-busy", "true");
        $.ajax({ url: $("#petaSebaran").data("filter-url"), dataType: "json", cache: false })
          .done(function (opts) {
            if (seq !== petaSeq) return;
            petaOptions = opts || {};
            petaCache = {};
            petaBuildFilters();
            petaSanitize();
            petaSyncControls();
            petaFetch(true);
          })
          .fail(function () {
            if (seq === petaSeq) {
              $("#petaMapWrap").attr("aria-busy", "false");
              petaStatus("Data peta gagal dimuat. Silakan coba lagi.", true);
            }
          });
      }

      function petaFetch(staleRetried) {
        var params = petaParams();
        var key = $.param(params);
        // Respons lama yang datang terlambat diabaikan
        var seq = ++petaSeq;

        if (petaCache[key]) {
          $("#petaMapWrap").attr("aria-busy", "false");
          petaRender(petaCache[key]);
          return;
        }

        $("#petaMapWrap").attr("aria-busy", "true");
        $.getJSON($("#petaSebaran").data("url"), params)
          .done(function (res) {
            if (!res || !Array.isArray(res.desa)) {
              if (seq === petaSeq) petaClear("Data peta tidak valid. Silakan coba lagi.");
              return;
            }
            petaCache[key] = res;
            if (seq === petaSeq) petaRender(res);
          })
          .fail(function (xhr) {
            if (seq !== petaSeq) return;
            petaClear("Data peta gagal dimuat. Silakan coba lagi.");
            if (xhr.status === 422 && staleRetried !== true) {
              petaStatus("Memperbarui pilihan filter…", false);
              petaRefreshOptions(seq);
            }
          })
          .always(function (resOrXhr, textStatus) {
            // Saat options dimuat ulang, busy direset oleh petaRefreshOptions/petaFetch berikutnya
            var refreshing = textStatus !== "success" && resOrXhr && resOrXhr.status === 422 && staleRetried !== true;
            if (seq === petaSeq && !refreshing) $("#petaMapWrap").attr("aria-busy", "false");
          });
      }

      function petaRender(res) {
        var rows = res.desa;
        var order = [];

        petaFocusCancel();
        petaMap.closePopup();
        petaLayer.clearLayers();
        // Bounds tetap mencakup semua desa berkoordinat (termasuk tier 0)
        petaBounds = L.latLngBounds([]);
        petaMarkers = rows.map(function (row, index) {
          var lat = parseFloat(row.latitude);
          var lng = parseFloat(row.longitude);
          if (!isFinite(lat) || !isFinite(lng)) return null;
          petaBounds.extend([lat, lng]);

          var tier = parseInt(row.tier, 10);
          if (!(tier >= 0 && tier < petaTiers.length)) tier = 0;
          // Tier 0 (tanpa mahasiswa) tidak digambar
          if (tier === 0) return null;
          var marker = L.circleMarker([lat, lng], {
            radius: petaTiers[tier].radius,
            color: petaTiers[tier].stroke,
            weight: tier > 0 ? 1.75 : 1.25,
            opacity: tier > 0 ? 1 : 0.85,
            dashArray: tier > 0 ? null : "2 2",
            fillColor: "url(#petaGrad" + tier + ")",
            fillOpacity: tier > 0 ? 0.82 : 0.6,
            // Tier 0 (kosong) tanpa pulse
            className: tier > 0 ? "peta-dot peta-bubble" : "peta-dot"
          }).bindPopup(petaPopup(row, res.periode))
            // Lepas buka-popup bawaan klik; popup dibuka petaFocus setelah animasi
            .off("click")
            .on("click", function (e) {
              L.DomEvent.stopPropagation(e);
              petaFocus(this);
            })
            .on("popupopen popupclose", function (e) {
              var el = this.getElement();
              if (el) el.classList.toggle("is-active", e.type === "popupopen");
            });
          order.push({ marker: marker, tier: tier });
          return marker;
        });

        // Lingkaran besar digambar dulu agar yang kecil tetap bisa diklik
        order.sort(function (a, b) {
          return b.tier - a.tier;
        }).forEach(function (item) {
          petaLayer.addLayer(item.marker);
          // Delay acak agar detak tidak serempak
          var el = item.tier > 0 && item.marker.getElement();
          if (el) el.style.animationDelay = (Math.random() * 2).toFixed(2) + "s";
        });

        petaSummary(res);
        petaSide(rows, res.periode);
        petaKecamatan(res);

        if (order.length === 0) {
          var tahunRes = res.filter && res.filter.tahun ? res.filter.tahun : petaFilter.tahun;
          petaStatus(rows.length === 0 && res.mode === "pt"
            ? "PT ini belum memiliki mahasiswa di tahun " + tahunRes
            : "Belum ada desa dengan mahasiswa untuk filter ini.", false);
          return;
        }
        petaStatus("", false);
        petaFit();
      }

      function petaFit() {
        petaMap.fitBounds(petaBounds && petaBounds.isValid() ? petaBounds : petaLayer.getBounds(), { padding: [30, 30], maxZoom: 14, animate: !petaReduceMotion });
        // Container tersembunyi (tab ditinggalkan) memberi ukuran 0; fit diulang saat tab dibuka
        petaFitted = document.getElementById("petaSebaran").offsetWidth > 0;
      }

      function petaSummary(res) {
        var summary = res.summary || {};
        var tahun = res.filter && res.filter.tahun ? res.filter.tahun : petaFilter.tahun;
        var ptMode = res.mode === "pt";
        var note = "mahasiswa KKN" + (ptMode ? " · " + $("#petaPt option:selected").text() : "");
        if (summary.desa_tersamar > 0) {
          note += ". " + summary.desa_tersamar + " desa dengan <3 mahasiswa disamarkan.";
        }

        $("#petaSubtitle").text("Persebaran di " + (summary.jumlah_desa == null ? 0 : summary.jumlah_desa) + " desa, " + (summary.jumlah_kecamatan == null ? 0 : summary.jumlah_kecamatan) + " kecamatan");
        $("#petaSummaryBadge").text(tahun ? "Tahun " + tahun : "Semua Tahun");
        $("#petaTotal").text(summary.total_label == null ? "0" : summary.total_label);
        $("#petaSummaryNote").text(note);
      }

      function petaSide(rows, periode) {
        var sorot = document.getElementById("petaSorot");
        var list = document.getElementById("petaTop");

        sorot.length = 1;
        rows.forEach(function (row, index) {
          if (petaMarkers[index]) {
            sorot.add(new Option(row.desa + (row.kecamatan ? " (" + row.kecamatan + ")" : ""), String(index)));
          }
        });

        var top = rows.map(function (row, index) {
          return { row: row, index: index };
        }).filter(function (item) {
          item.persen = petaPersen(item.row.persen_pengurangan);
          return petaMarkers[item.index] && item.persen !== null;
        }).sort(function (a, b) {
          // Persen DESC, lalu nama desa
          return b.persen - a.persen || a.row.desa.localeCompare(b.row.desa, "id");
        }).slice(0, 5);

        list.textContent = "";
        top.forEach(function (item, rank) {
          var li = petaEl("li");
          var btn = petaEl("button", "peta-top-item");
          var body = petaEl("span", "peta-top-body");
          var count = petaEl("span", "text-end");

          btn.type = "button";
          btn.setAttribute("data-index", String(item.index));
          btn.setAttribute("aria-label", "Sorot " + item.row.desa + ", pengurangan sampah " + petaPersenText(item.persen));
          btn.appendChild(petaEl("span", "peta-rank" + (rank < 3 ? " peta-rank-" + (rank + 1) : ""), "#" + (rank + 1)));
          body.appendChild(petaEl("span", "peta-top-name", item.row.desa));
          if (item.row.kecamatan) {
            body.appendChild(petaEl("span", "badge rounded-pill bg-label-primary mt-1", item.row.kecamatan));
          }
          body.appendChild(petaEl("span", "peta-top-reduksi", item.row.jumlah_label + " mahasiswa"));
          btn.appendChild(body);
          count.appendChild(petaEl("span", "peta-top-count d-block", petaPersenText(item.persen)));
          count.appendChild(petaEl("small", "peta-muted", periode && periode.label ? periode.label : "pengurangan"));
          btn.appendChild(count);
          li.appendChild(btn);
          list.appendChild(li);
        });
        $("#petaTopEmpty")
          .text("Belum ada data pengurangan sampah untuk filter ini.")
          .toggleClass("d-none", top.length > 0);
      }

      function petaKecamatan(res) {
        var list = document.getElementById("petaKec");
        var items = petaList(res.kecamatan);

        list.textContent = "";
        $("#petaKecPeriode").text(res.periode && res.periode.label ? "Periode " + res.periode.label : "");
        items.forEach(function (item) {
          var persen = petaPersen(item.persen_pengurangan);
          var li = petaEl("li");
          var head = petaEl("div", "peta-kec-row");
          var bar = petaEl("div", "peta-kec-bar");

          head.appendChild(petaEl("span", "peta-kec-name", item.kecamatan));
          head.appendChild(petaEl("span", "peta-kec-value" + (persen === null ? " is-empty" : ""), persen === null ? "Belum ada data" : petaPersenText(persen)));
          li.appendChild(head);
          if (persen !== null) {
            // Klaster warna mengikuti laporan publik: >20 hijau, >=10 kuning, <10 merah
            var fill = petaEl("div", "peta-kec-fill " + (persen > 20 ? "is-high" : persen >= 10 ? "is-mid" : "is-low"));
            fill.style.width = Math.min(100, Math.max(0, persen)) + "%";
            bar.appendChild(fill);
          }
          bar.setAttribute("aria-hidden", "true");
          li.appendChild(bar);
          list.appendChild(li);
        });
        $("#petaKecEmpty").toggleClass("d-none", items.length > 0);
      }

      function petaFocusCancel() {
        if (petaFocusPending) petaMap.off("moveend", petaFocusPending);
        petaFocusPending = null;
      }

      // Dipakai Sorot Desa, Top 5, dan klik bubble (index atau marker)
      function petaFocus(target) {
        var marker = typeof target === "number" ? petaMarkers[target] : target;
        if (!marker) return;

        var latlng = marker.getLatLng();
        // Tidak pernah zoom out bila sudah lebih dekat
        var zoom = Math.max(petaMap.getZoom(), 14);
        petaFocusCancel();
        petaMap.closePopup();

        // Di mobile panel berada di bawah peta (klik bubble sudah di peta)
        if (typeof target === "number" && window.innerWidth < 992) {
          document.getElementById("petaSebaran").scrollIntoView({ behavior: petaReduceMotion ? "auto" : "smooth", block: "center" });
        }

        if (petaReduceMotion) {
          petaMap.setView(latlng, zoom, { animate: false });
          marker.openPopup();
          return;
        }
        // on/off manual (bukan once) agar bisa dibatalkan bersih saat klik beruntun
        petaFocusPending = function () {
          petaFocusCancel();
          if (petaLayer.hasLayer(marker)) marker.openPopup();
        };
        petaMap.on("moveend", petaFocusPending);
        petaMap.flyTo(latlng, zoom, { duration: 0.8 });
      }

      function petaReset() {
        var years = petaList(petaOptions.tahun);
        var year = parseInt(petaOptions.tahun_default, 10) || parseInt(years[0], 10) || null;
        petaFilter = { tahun: year, kodept: null };
        petaSyncControls();
        clearTimeout(petaTimer);
        petaFetch();
      }

      function petaBootFail() {
        petaBooting = false;
        petaStatus("Peta gagal dimuat. Silakan coba lagi.", true);
      }

      function petaBoot() {
        if (petaBooting) return;
        petaBooting = true;
        petaStatus("Memuat peta…", false);
        petaLoadLeaflet(function () {
          $.getJSON($("#petaSebaran").data("filter-url"))
            .done(function (opts) {
              petaBooting = false;
              petaOptions = opts || {};
              petaInitMap();
              petaBuildFilters();
              petaReset();
            })
            .fail(petaBootFail);
        }, petaBootFail);
      }

      // Pastikan pill aktif terlihat saat toggle di-scroll horizontal
      $(".panel-switch").on("shown.bs.tab", ".nav-link", function () {
        const bar = this.parentElement;
        if (bar.scrollWidth <= bar.clientWidth) return;
        const pill = this.getBoundingClientRect();
        const box = bar.getBoundingClientRect();
        const left = bar.scrollLeft + (pill.left - box.left) - (box.width - pill.width) / 2;
        const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        bar.scrollTo({ left: Math.max(0, left), behavior: reduce ? "auto" : "smooth" });
      });

      $("#tabPeta").on("shown.bs.tab", function () {
        if (petaMap) {
          petaMap.invalidateSize();
          if (!petaFitted && petaLayer.getLayers().length) petaFit();
          return;
        }
        petaBoot();
      });

      $("#petaRetry").on("click", function () {
        if (petaMap) {
          petaFetch();
        } else {
          petaBoot();
        }
      });

      $("#petaTahun").on("change", function () {
        petaFilter.tahun = parseInt(this.value, 10) || null;
        petaSchedule();
      });

      $("#petaPt").on("change", function () {
        petaFilter.kodept = this.value || null;
        petaSchedule();
      });

      $("#petaReset").on("click", function () {
        if (petaOptions && petaMap) petaReset();
      });

      $("#petaSorot").on("change", function () {
        if (this.value === "") return;
        petaFocus(parseInt(this.value, 10));
        // Kembali ke placeholder agar desa yang sama bisa dipilih ulang
        this.value = "";
        $(this).trigger("change.select2");
      });

      $("#petaTop").on("click", ".peta-top-item", function () {
        petaFocus(parseInt($(this).attr("data-index"), 10));
      });

      // Link #panduan bisa dibagikan untuk langsung membuka tab Panduan
      if (window.location.hash === "#panduan") {
        bootstrap.Tab.getOrCreateInstance(document.getElementById("tabPanduan")).show();
        setMobilePanelVisible(true);
      }

})
</script>
