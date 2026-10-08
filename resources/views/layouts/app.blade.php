<!doctype html>

<html
  lang="en"
  class="light-style layout-navbar-fixed layout-wide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="../../assets/"
  data-template="front-pages"
  data-style="light">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KKN Tematik Sampah Tuntas</title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../../assets/images/icon.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="../../assets/vendor/fonts/remixicon/remixicon.css" />

    <!-- Menu waves for no-customizer fix -->
    <link rel="stylesheet" href="../../assets/vendor/libs/node-waves/node-waves.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="../../assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../../assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../../assets/css/demo.css" />
    <link rel="stylesheet" href="../../assets/css/action-buttons.css" />
    <link rel="stylesheet" href="{{ asset('css/laporan.css') }}?v={{ filemtime(public_path('css/laporan.css')) }}" />
    <link rel="stylesheet" href="../../assets/vendor/css/pages/front-page.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="../../assets/vendor/libs/nouislider/nouislider.css" />
    <link rel="stylesheet" href="../../assets/vendor/libs/swiper/swiper.css" />    
    <link rel="stylesheet" href="../../assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="../../assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.css" />  

    <!-- Page CSS -->
    <link rel="stylesheet" href="../../assets/vendor/css/pages/page-profile.css" />

    <link rel="stylesheet" href="../../assets/vendor/css/pages/front-page-landing.css" />
    <link rel="stylesheet" href="../../assets/vendor/libs/toastr/toastr.css" />

    <!-- Helpers -->
    <script src="../../assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="../../assets/vendor/js/template-customizer.js"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="../../assets/js/front-config.js"></script>
    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>

    <link rel="stylesheet" href="../../assets/vendor/libs/select2/select2.css" />

    <style>
      /* Keep footer pinned to the bottom when page content is short */
      body {
        display: flex;
        min-height: 100vh;
        flex-direction: column;
      }
      .first-section-pt {
        flex: 1 0 auto;
      }
      .landing-footer {
        flex-shrink: 0;
      }
      /* Semua header tabel rata tengah; selector html:not(...) mengalahkan .text-start/.text-end (!important) dari className kolom DataTables */
      table thead th,
      html:not([dir=rtl]) table thead th:is(.text-start, .text-end) {
        text-align: center !important;
        vertical-align: middle !important;
      }
    </style>
  </head>

  <body>
    <script src="../../assets/vendor/js/dropdown-hover.js"></script>
    <script src="../../assets/vendor/js/mega-dropdown.js"></script>

    @include("layouts.menu")
    <!-- Sections:Start -->

    <section class="section-py bg-body first-section-pt">
      <div class="container">
          @yield("container")
      </div>
    </section>

    <!-- / Sections:End -->
    <div class="modal fade" id="modalku" tabindex="-1" data-backdrop="static" data-keyboard="false" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <p id="modalisi">loading content...</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              <i class="ri-close-line me-2"></i>
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer: Start -->
    <footer class="landing-footer">
      <div class="footer-top position-relative overflow-hidden">
        <img src="../../assets/img/front-pages/backgrounds/footer-bg.png" alt="footer bg" class="footer-bg banner-bg-img" />
        <div class="container position-relative">
          <div class="row gx-0 gy-7 gx-sm-6 gx-lg-12">
            <div class="col-lg-5">
              <a href="#" class="app-brand-link mb-6">
                <span class="app-brand-logo demo me-2">
                  <span style="color: #666cff">
                    <img src="../../assets/images/lldikti4_logo.png" height="35">
                  </span>
                </span>
              </a>
              <p class="footer-text footer-logo-description mb-6">
                KKN Tematik merupakan implementasi Program GRADASI LLDIKTI Wilayah IV yang memberikan pengalaman belajar berbasis pengabdian melalui kolaborasi perguruan tinggi, pemerintah daerah, dunia usaha, dan masyarakat untuk mewujudkan pembangunan berkelanjutan.
              <span class="text-warning"></span>
              </p>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
              <h6 class="footer-title mb-4 mb-lg-6">Link Terkait</h6>
              <ul class="list-unstyled mb-0">
                <li class="mb-4">
                  <a href="https://lldikti4.kemdiktisaintek.go.id" target="_blank" class="footer-link">Laman LLDIKTI Wilayah IV</a>
                </li>
                <li class="mb-4">
                  <a href="https://pddikti.kemdiktisaintek.go.id" target="_blank" class="footer-link">PDDIKTI</a>
                </li>
                <li class="mb-4">
                  <a href="https://jurnal.lldikti4.or.id" target="_blank" class="footer-link">Jurnal LLDIKTI Wilayah IV</a>
                </li>
                <!--
                <li class="mb-4">
                  <a href="https://mbkm.lldikti4.id" target="_blank" class="footer-link">MBKM LLDIKTI IV 2023</a>
                </li>
                <li class="mb-4">
                  <a href="https://pusatinformasi.kampusmerdeka.kemdikbud.go.id" target="_blank" class="footer-link">Pusat Informasi Kampus Merdeka</a>
                </li>
                <li class="mb-4">
                  <a href="https://kampusmerdeka.kemdikbud.go.id/" target="_blank" class="footer-link">Kampus Merdeka</a>
                </li>
-->
              </ul>
            </div>
            <div class="col">
              <h6 class="footer-title mb-4 mb-lg-6">Kontak Kami</h6>
              <ul class="list-unstyled mb-0">
                <li class="mb-4">
                  <span class="footer-link">Jalan Penghulu H. Hasan Mustofa No. 38 Bandung 40124</span>
                </li>
                <li class="mb-4">
                  <a href="mailto:informasi@lldikti4.id" class="footer-link">informasi@lldikti4.id</a>
                </li>
                <li class="mb-4">
                  <span class="footer-link">Telepon: +022 7275630, +022 7274377</span>
                </li>
              </ul>
            </div>
            
          </div>
        </div>
      </div>
      <div class="footer-bottom py-5">
        <div
          class="container d-flex flex-wrap justify-content-between flex-md-row flex-column text-center text-md-start">
          <div class="mb-2 mb-md-0">
            <span class="footer-text">© {{ date('Y') }}, LLDIKTI Wilayah IV</span>
          </div>
          <div>
            <a href="https://www.facebook.com/lldiktiwilayah4/?tsid=0.24115179413463506&source=result" class="footer-link me-4" target="_blank"><i class="ri-facebook-circle-fill"></i></a>
            <a href="https://x.com/lldiktiwilayah4?s=09" class="footer-link me-4" target="_blank"><i class="ri-twitter-fill"></i></a>
            <a href="https://www.youtube.com/c/LLDIKTIWILAYAH4" class="footer-link me-4" target="_blank"><i class="ri-youtube-fill"></i></a>
            <a href="https://www.instagram.com/lldiktiwilayah4?utm_medium=copy_link" class="footer-link" target="_blank"><i class="ri-instagram-line"></i></a>
          </div>
        </div>
      </div>
    </footer>
    <!-- Footer: End -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="../../assets/vendor/libs/nouislider/nouislider.js"></script>
    <script src="../../assets/vendor/libs/swiper/swiper.js"></script>    
    <script src="../../assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>    
    <script src="../../assets/vendor/libs/toastr/toastr.js"></script>
    <script src="../../assets/vendor/libs/select2/select2.js"></script>

    <!-- Main JS -->
    <script src="../../assets/js/front-main.js"></script>
    <script src="../../assets/js/ui-toasts.js"></script>
    {{-- ?v= berganti tiap file berubah agar browser tidak memakai cache lama --}}
    <script src="{{ asset('js/crud.js') }}?v={{ filemtime(public_path('js/crud.js')) }}"></script>
    <script src="{{ asset('js/search-select.js') }}?v={{ filemtime(public_path('js/search-select.js')) }}"></script>
    <script src="{{ asset('js/grouped-table.js') }}?v={{ filemtime(public_path('js/grouped-table.js')) }}"></script>

    <!-- Page JS -->
    <script src="../../assets/js/front-page-landing.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs5.min.js"></script>
  </body>
</html>

<script type="text/javascript">  
	$(function(){
		$('body').on("click","a.modalButton,button.modalButton",function(){
			var src = $(this).attr('data-src');
			var title = $(this).attr("title");
			if(!title){
				title =  $(this).attr("data-original-title");
			}
			if(!src || src.length == 0){
				return false;
			}
			$(".modal-title").text(title);
			//$('.modal').modal();        
			$('#modalisi').html('Loading, mohon tunggu...');
			$('#modalisi').load(src);
		})

	})
</script>
