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
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>PPS Bandung | Login</title>

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
          opacity: 0.1;
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
          align-items: flex-start;
          justify-content: left;
          background: #fff;
          border-radius: 14px;
          padding: 0.55rem 0.95rem;
          box-shadow: 0 8px 20px rgba(0, 0, 0, 0.14);
          margin-bottom: 0.9rem;
        }

        .lokasi-header-logo img {
          width: 90px;
          height: auto;
          display: block;
          margin-bottom: 0.1rem;
        }

        .lokasi-panel-title {
          margin-top: 3rem;
          color: #fff;
          font-family: 'Inter', sans-serif;
          font-weight: 900;
          font-size: 50px;
          line-height: 1.1;
        }

        .lokasi-panel-subtitle {
          color: rgba(255, 255, 255, 0.9);
          font-size: 17pt;
          margin-bottom: 2rem;
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
          border-radius: 10px;
          overflow: hidden;
          box-shadow: 0 6px 18px rgba(31, 41, 55, 0.08);
          border: 1px solid rgba(102, 126, 234, 0.12);
          transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
          cursor: pointer;
          position: relative;
        }

        .lokasi-item:hover {
          transform: translateY(-4px);
          box-shadow: 0 14px 28px rgba(102, 126, 234, 0.22);
          border-color: rgba(102, 126, 234, 0.35);
        }

        /* State terpilih — dipakai kalau JS toggle class .active saat lokasi diklik */
        .lokasi-item.active {
          border-color: #667eea;
          box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.18), 0 14px 28px rgba(102, 126, 234, 0.22);
        }

        .lokasi-item.active::after {
          content: '\ec8e'; /* ri-check-fill (Remix Icon) */
          font-family: 'remixicon';
          position: absolute;
          top: 8px;
          right: 8px;
          width: 22px;
          height: 22px;
          background: #667eea;
          color: #fff;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 0.75rem;
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
          padding: 10px 12px 12px;
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
          padding: 6px 4px;
          background: rgba(102, 126, 234, 0.06);
          border-radius: 8px;
        }

        .lokasi-stat i {
          font-size: 0.95rem;
          color: #667eea;
          margin-bottom: 2px;
        }

        .lokasi-stat-value {
          font-weight: 700;
          font-size: 0.85rem;
          color: #1f2937;
          line-height: 1.2;
        }

        .lokasi-stat-label {
          font-size: 0.65rem;
          color: #6b7280;
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
          font-size: 0.78rem;
          font-weight: 600;
          color: #667eea;
          background: rgba(102, 126, 234, 0.12);
          padding: 0.35rem 0.75rem;
          border-radius: 999px;
          margin-bottom: 0.75rem;
        }

        .selected-lokasi-name {
          color: #3b4663;
          font-weight: 600;
          margin-top: -0.15rem;
          margin-bottom: 0.35rem;
          line-height: 1.15;
        }

        .login-title {
          margin-bottom: 0.2rem;
          line-height: 1.1;
        }
      
      /* Form Section */
      .authentication-bg {
          background-color: rgba(255, 255, 255, 0.98);
          backdrop-filter: blur(20px);
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
          border-radius: 24px 0px 0px 24px;
      }
      
      .form-control:focus {
          border-color: #696cff;
          box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25);
      }
      
      .btn-primary {
          padding: 0.75rem 1.5rem;
          font-weight: 500;
          width: 100%;
          background: #667eea;
          border: none;
          transition: all 0.3s ease;
      }
      
      .btn-primary:hover {
          transform: translateY(-2px);
          box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
      }
      
      .btn-icon {
          width: 38px;
          height: 38px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          transition: all 0.3s ease;
          border: 2px solid #e7e7ff;
      }
      
      .btn-icon:hover {
          transform: translateY(-3px);
          box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
          border-color: #667eea;
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

        @media (max-width: 1399px) {
          .lokasi-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
          }
        }

        @media (max-width: 1199px) {
          .lokasi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
          }
        }

        /* Mobile switch button (left/right view) */
        .auth-mobile-toggle {
          display: none;
        }

        @media (max-width: 991.98px) {
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
            border: none;
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
            <div class="lokasi-panel-header">
              {{-- Logo Header --}}
              <div class="d-flex justify-content-start">
                <div class="lokasi-header-logo">
                  <img src="../../assets/images/logo-kkn-berdampak.jpeg" alt="KKN Tematik Berdampak" />
                  <img src="../../assets/images/gradasi.png" alt="Gradasi 4" style="width: 110px; height: auto;" />
                </div>
              </div>
              <h2 class="lokasi-panel-title">Pilot Pemungut Sampah Bandung <br /> LLDIKTI Wilayah IV</h2>
              <p class="lokasi-panel-subtitle">
                <b>LOKASI PELAKSAAN PROGRAM</b>
                <br />
                Silahkan pilih Lokasi Program terlebih dahulu sebelum Login ke Aplikasi PPS Bandung
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
                            <span class="lokasi-stat-label">PT</span>
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
        </div>
        <!-- /Left Section -->

        <!-- Login Form -->
        <div class="auth-right-panel d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg position-relative py-5 px-4 px-sm-5">
          <div class="w-100 mx-auto" style="max-width: 400px;">
            <div class="mb-4 text-center">
              <img src="../../assets/images/lldikti4_logo.png" alt="LLDIKTI Wilayah IV" style="width: 200px; height: auto; margin-bottom: 30px;" />
              
              <h4 class="login-title fw-bold">PPS Bandung</h4>
              <h4 id="selectedLokasiName" class="selected-lokasi-name fw-bold"></h4>
              <p class="mb-0 text-muted">Silakan login untuk masuk ke Dashboard</p>
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
                  placeholder="Enter your email or username"
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
                      <label for="password">Password</label>
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
                <a href="https://wa.me/082244121226?text=Halo%20saya%20ingin%20bertanya" class="fw-semibold">LLDIKTI Wilayah IV</a>
              </p>
            </div>

            <div class="text-center mt-4">
              <div class="d-flex justify-content-center gap-2">
                <a href="https://www.facebook.com/lldiktiwilayah4/?tsid=0.24115179413463506&source=result" target="_blank" class="btn btn-icon rounded-circle btn-text-facebook" title="Facebook">
                  <i class="ri-facebook-fill"></i>
                </a>
                <a href="https://x.com/lldiktiwilayah4?s=09" target="_blank" class="btn btn-icon rounded-circle btn-text-twitter" title="Twitter">
                  <i class="ri-twitter-fill"></i>
                </a>
                <a href="https://www.youtube.com/c/LLDIKTIWILAYAH4" target="_blank" class="btn btn-icon rounded-circle btn-text-google-plus" title="YouTube">
                  <i class="ri-youtube-fill"></i>
                </a>
                <a href="https://www.instagram.com/lldiktiwilayah4?utm_medium=copy_link" target="_blank" class="btn btn-icon rounded-circle btn-text-google-plus" title="Instagram">
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
  </body>
</html>
<script>
$(function(){
    $("#formAuthentication").on("submit",function(){      
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
            toastr.warning(ret.messages)
            if(ret.messages && ret.messages.indexOf("lokasi program") !== -1){
              $(".authentication-inner").addClass("show-lokasi");
              $("#authMobileToggleText").text("Kembali ke Login");
              $("#authMobileToggle i").removeClass("ri-map-pin-2-line").addClass("ri-arrow-left-line");
            }
          }
        },
        error:function(xhr,ajaxOptions,thrownError){
          alert(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
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
        $("#lokasiGrid .lokasi-item").removeClass("active");
        $(this).addClass("active");
        $("#selectedLokasiName").text(lokasiName);
        $("#lokasi").val(lokasiName);
      });

      $("#authMobileToggle").on("click", function () {
        var $inner = $(".authentication-inner");
        $inner.toggleClass("show-lokasi");
        var showingLokasi = $inner.hasClass("show-lokasi");
        $("#authMobileToggleText").text(showingLokasi ? "Kembali ke Login" : "Lihat Lokasi Program");
        $(this).find("i").toggleClass("ri-map-pin-2-line ri-arrow-left-line");
      });
    
})
</script>