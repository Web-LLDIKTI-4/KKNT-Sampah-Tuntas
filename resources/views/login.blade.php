{{-- Captcha aktif hanya bila backend juga bisa memverifikasi (site + secret key) --}}
@php($recaptchaSiteKey = app(\App\Services\RecaptchaService::class)->siteKey())
@php($lokasiProgramList ??= collect())
@php($lokasiTerpilih = old('lokasi', $selectedLokasi ?? null))
<!doctype html>

<html
  lang="id"
  class="light-style layout-wide customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../../assets/"
  data-template="vertical-menu-template"
  data-style="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>KKN Tematik Sampah Tuntas | Masuk</title>

    <meta name="description" content="Masuk ke dashboard KKN Tematik Sampah Tuntas LLDIKTI Wilayah IV." />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/images/icon.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="../../assets/vendor/fonts/remixicon/remixicon.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../../assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../../assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../../assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../../assets/vendor/libs/@form-validation/form-validation.css" />
    <link rel="stylesheet" href="../../assets/vendor/css/pages/page-auth.css" />
    <link rel="stylesheet" href="../../assets/vendor/libs/toastr/toastr.css" />

    <!-- Helpers -->
    <script src="../../assets/vendor/js/helpers.js"></script>
    <script src="../../assets/vendor/js/template-customizer.js"></script>
    <script src="../../assets/js/config.js"></script>

    <style>
      .auth-page {
        position: relative;
        min-height: 100vh;
        min-height: 100dvh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background:
          radial-gradient(50rem 26rem at 90% -10%, rgba(52, 211, 153, 0.2), transparent 60%),
          radial-gradient(40rem 30rem at -10% 110%, rgba(102, 126, 234, 0.45), transparent 60%),
          linear-gradient(160deg, #1e1b4b, #3730a3);
      }

      .auth-page::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url('../../assets/images/bg-image.jpg') center / cover no-repeat;
        opacity: 0.06;
        pointer-events: none;
      }

      .auth-card {
        position: relative;
        width: 100%;
        max-width: 440px;
        padding: 1.5rem 1.25rem;
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 30px 60px rgba(8, 10, 40, 0.35);
      }

      .auth-back {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        margin-bottom: 1.25rem;
        color: #5d6178;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
      }

      .auth-back:hover {
        color: #4f5fd8;
      }

      .auth-logos {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
      }

      .auth-logos img {
        height: 56px;
        width: auto;
      }

      .auth-logos .auth-lldikti {
        height: 40px;
        max-width: 150px;
        object-fit: contain;
      }

      .auth-title {
        margin-bottom: 0.25rem;
        color: #1b1f3b;
        font-weight: 800;
        font-size: 1.35rem;
        letter-spacing: -0.02em;
      }

      .auth-sub {
        margin-bottom: 1.5rem;
        color: #5d6178;
        font-size: 0.9rem;
      }

      .form-control:focus,
      .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.2);
      }

      .form-floating-outline .form-control,
      .form-floating-outline .form-select {
        height: calc(3.5rem + 2px);
      }

      .auth-card .btn-primary {
        width: 100%;
        padding: 0.8rem 1.5rem;
        font-weight: 600;
        background: #667eea;
        border: none;
        border-radius: 10px;
        box-shadow: 0 6px 18px rgba(102, 126, 234, 0.3);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }

      .auth-card .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(102, 126, 234, 0.45);
      }

      .auth-card .btn-primary:focus-visible {
        outline: 3px solid rgba(102, 126, 234, 0.45);
        outline-offset: 2px;
      }

      .auth-card .btn-primary:disabled {
        transform: none;
        opacity: 0.8;
      }

      .auth-social {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
      }

      .auth-social a {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #667eea;
        background: #f4f5ff;
        border: 1px solid #e7e7ff;
        transition: background-color 0.2s ease, color 0.2s ease;
      }

      .auth-social a:hover,
      .auth-social a:focus-visible {
        color: #fff;
        background: #667eea;
      }

      @media (min-width: 576px) {
        .auth-page {
          padding: 2rem;
        }

        .auth-card {
          padding: 2.25rem 2.25rem 2rem;
        }
      }

      @media (prefers-reduced-motion: reduce) {
        .auth-card .btn-primary,
        .auth-social a {
          transition: none;
        }

        .auth-card .btn-primary:hover {
          transform: none;
        }
      }

      .grecaptcha-badge {
        z-index: 1060;
      }
    </style>
  </head>

  <body>
    <main class="auth-page">
      <div class="auth-card">
        <a href="{{ route('landing') }}" class="auth-back">
          <i class="ri-arrow-left-line" aria-hidden="true"></i> Kembali ke Beranda
        </a>

        <div class="text-center">
          <div class="auth-logos">
            <img src="../../assets/images/logo-kkn-berdampak.jpeg" alt="KKN Tematik Berdampak" />
            <img src="../../assets/images/gradasi.png" alt="Gradasi 4" />
            <img src="../../assets/images/lldikti4_logo.png" alt="LLDIKTI Wilayah IV" class="auth-lldikti" />
          </div>
          <h1 class="auth-title">Masuk ke Dashboard</h1>
          <p class="auth-sub">KKN Tematik Sampah Tuntas</p>
        </div>

        <form id="formAuthentication" class="mb-4" action="{{ url('login') }}" method="POST" novalidate>
          @csrf
          @method('PUT')
          @if($recaptchaSiteKey)
            <input type="hidden" name="g-recaptcha-response" value="" />
          @endif

          <div class="mb-5">
            <div class="form-floating form-floating-outline">
              <select id="lokasi" name="lokasi" class="form-select" data-no-search aria-describedby="lokasiHelp">
                <option value="">— Pilih lokasi program —</option>
                @foreach ($lokasiProgramList as $lokasi)
                  <option value="{{ $lokasi->nama_lokasi }}" @selected($lokasi->nama_lokasi === $lokasiTerpilih)>{{ $lokasi->nama_lokasi }}</option>
                @endforeach
              </select>
              <label for="lokasi">Lokasi Program</label>
            </div>
            <div id="lokasiHelp" class="form-text">Wajib untuk DPL &amp; Mahasiswa.</div>
          </div>

          <div class="form-floating form-floating-outline mb-5">
            <input
              type="text"
              class="form-control"
              id="email"
              name="username"
              placeholder="Masukkan username"
              autocomplete="username"
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
                    autocomplete="current-password"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                    aria-describedby="password" />
                  <label for="password">Kata Sandi</label>
                </div>
                <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
              </div>
            </div>
          </div>
          <div class="mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="remember-me" />
              <label class="form-check-label" for="remember-me">Ingat Saya</label>
            </div>
          </div>
          <button class="btn btn-primary" id="btnSubmit_formAuthentication">
            <i class="ri-lock-fill me-2"></i>Masuk
          </button>
        </form>

        <p class="text-center small mb-4">
          <span class="text-muted">Belum punya akun? Kontak </span>
          <a href="https://wa.me/6282244121226?text=Halo%20saya%20ingin%20bertanya" target="_blank" rel="noopener noreferrer" class="fw-semibold">LLDIKTI Wilayah IV</a>
        </p>

        <div class="auth-social">
          <a href="https://www.facebook.com/lldiktiwilayah4/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="ri-facebook-fill" aria-hidden="true"></i></a>
          <a href="https://x.com/lldiktiwilayah4" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)"><i class="ri-twitter-x-line" aria-hidden="true"></i></a>
          <a href="https://www.youtube.com/c/LLDIKTIWILAYAH4" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="ri-youtube-fill" aria-hidden="true"></i></a>
          <a href="https://www.instagram.com/lldiktiwilayah4" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="ri-instagram-fill" aria-hidden="true"></i></a>
        </div>
      </div>
    </main>

    <!-- Core JS -->
    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>
    <script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../../assets/vendor/libs/hammer/hammer.js"></script>
    <script src="../../assets/vendor/libs/i18n/i18n.js"></script>
    <script src="../../assets/vendor/js/menu.js"></script>

    <!-- Main JS (toggle password) -->
    <script src="../../assets/js/main.js"></script>

    <script src="../../assets/vendor/libs/toastr/toastr.js"></script>
    @if($recaptchaSiteKey)
      <script src="https://www.google.com/recaptcha/api.js?render={{ urlencode($recaptchaSiteKey) }}" async defer></script>
    @endif
    <script>
    $(function(){

      // Token CSRF diperbarui dari respons server, tanpa reload halaman
      function setToken(token) {
        if (!token) return;
        $("#formAuthentication input[name='_token']").val(token);
        $('meta[name="csrf-token"]').attr("content", token);
      }

      var recaptchaSiteKey = @json($recaptchaSiteKey);
      var $lokasi = $("#lokasi");

      $lokasi.on("change", function () {
        $(this).removeClass("is-invalid");
      });

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
              if(ret.messages && String(ret.messages).toLowerCase().indexOf("lokasi program") !== -1){
                $lokasi.addClass("is-invalid").trigger("focus");
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

    })
    </script>
  </body>
</html>
