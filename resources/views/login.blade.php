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

    <title>LLDIKTI IV | Login page</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/images/icon.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
      rel="stylesheet" />

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
          background: url('../../assets/images/6907397.jpg') no-repeat center center;
          background-size: cover;
          opacity: 0.1;
      }
      
      /* Modern Card Kiri */
      .welcome-card {
          background: #667eea;
          border-radius: 24px;
          box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
          border: none;
          overflow: hidden;
          position: relative;
      }
      
      .welcome-card::before {
          content: '';
          position: absolute;
          top: -50%;
          right: -50%;
          width: 200%;
          height: 200%;
          background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
          animation: pulse 15s ease-in-out infinite;
      }
      
      @keyframes pulse {
          0%, 100% { transform: scale(1) rotate(0deg); }
          50% { transform: scale(1.1) rotate(180deg); }
      }
      
      .welcome-card .card-header {
          background: rgba(255, 255, 255, 0.15);
          backdrop-filter: blur(10px);
          border: none;
          border-bottom: 1px solid rgba(255, 255, 255, 0.2);
          padding: 2rem;
          position: relative;
          z-index: 1;
      }
      
      .welcome-card .card-body {
          padding: 3rem 2.5rem;
          position: relative;
          z-index: 1;
      }
      
      .welcome-card .illustration-wrapper {
          background: rgba(255, 255, 255, 0.15);
          backdrop-filter: blur(10px);
          border-radius: 20px;
          padding: 2rem;
          margin-bottom: 2rem;
          border: 1px solid rgba(255, 255, 255, 0.2);
          box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      }
      
      .welcome-card .program-title {
          color: #ffffff;
          font-weight: bold;
          font-size: 2rem;
          margin-bottom: 1rem;
          text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
      }
      
      .welcome-card .welcome-text {
          color: rgba(255, 255, 255, 0.95);
          font-weight: 500;
          line-height: 1.7;
          font-size: 1.5rem;
      }
      
      /* Badge decoratif */
      .security-badge {
          position: absolute;
          top: 20px;
          right: 20px;
          background: rgba(255, 255, 255, 0.2);
          backdrop-filter: blur(10px);
          border-radius: 50px;
          padding: 0.5rem 1rem;
          color: white;
          font-size: 0.75rem;
          font-weight: 600;
          border: 1px solid rgba(255, 255, 255, 0.3);
          display: flex;
          align-items: center;
          gap: 0.5rem;
      }
      
      .security-badge i {
          font-size: 1rem;
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
          .welcome-card .card-body {
              padding: 2rem 1.5rem;
          }
          
          .welcome-card .program-title {
              font-size: 1.5rem;
          }
          
          .welcome-card .welcome-text {
              font-size: 1rem;
          }

          .authentication-bg {
              border-radius: 0px;
          }
      }
      </style>


  </head>

  <body>
    <!-- Content -->

    <div class="authentication-wrapper authentication-cover">
      <!-- Logo -->
      <a href="{{ url('/') }}" class="auth-cover-brand d-flex align-items-center gap-2">
        <span class="app-brand-logo demo">
          <span style="color: var(--bs-primary)">
            
          </span>
        </span>
      </a>
      <!-- /Logo -->
      <div class="authentication-inner row m-0">
        <!-- Left Section -->
        <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center justify-content-center p-5">
          <div class="card welcome-card" style="max-width: 650px; width: 100%;">
            <div class="security-badge">
              <i class="ri-shield-check-line"></i>
              <span>Secure Login</span>
            </div>
            
            <div class="card-header text-center">
              <div style="background: white; padding: 0.75rem 1.5rem; border-radius: 12px; display: inline-block; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <img src="../assets/images/logos.png" alt="LLDIKTI Logo" style="max-height: 75px; width: auto;">
              </div>
            </div>
            
            <div class="card-body text-center mt-5">
              <div class="illustration-wrapper">
                <img class="img-fluid" src="../../assets/images/illustrations-login.png" alt="Illustration" style="max-width: 280px;">
              </div>
              
              <h2 class="program-title">KKN Mahasiswa {{ date('Y') }} <br /> LLDIKTI Wilayah IV</h2>
              {{-- <p class="welcome-text px-3">
                
              </p> --}}
            </div>
          </div>
        </div>
        <!-- /Left Section -->

        <!-- Login Form -->
        <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg position-relative py-5 px-4 px-sm-5">
          <div class="w-100 mx-auto" style="max-width: 400px;">
            <div class="mb-4 text-center">
              <h4 class="mb-2 fw-bold">LOGIN</h4>
              <p class="mb-0 text-muted">Silakan login untuk masuk ke Dashboard</p>
            </div>

            <form id="formAuthentication" class="mb-5" action="{{ url('login') }}" method="POST">
              @csrf
              @method('PUT')
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
                  <label class="form-check-label" for="remember-me">Remember Me</label>
                </div>
                <a href="#" class="text-primary">
                  <small>Lupa Kata Sandi?</small>
                </a>
              </div>
              <button class="btn btn-primary" id="btnSubmit_formAuthentication">
                <i class="ri-lock-fill me-2"></i>Sign In
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
</html><script>
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
            document.location="{{ url('home') }}";   
          }else{
            toastr.warning(ret.messages)
          }
        },
        error:function(xhr,ajaxOptions,thrownError){
          alert(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
        }			
      })
      return false;
    })
    
})
</script>