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
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>LLDIKTI IV - KKN Nusantara</title>

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
    <link rel="stylesheet" href="../../assets/vendor/css/pages/front-page.css" />
    <!-- Vendors CSS -->

    <link rel="stylesheet" href="../../assets/vendor/libs/nouislider/nouislider.css" />
    <link rel="stylesheet" href="../../assets/vendor/libs/swiper/swiper.css" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="../../assets/vendor/libs/toastr/toastr.css" />

    <link rel="stylesheet" href="../../assets/vendor/css/pages/front-page-landing.css" />

    <!-- Helpers -->
    <script src="../../assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="../../assets/vendor/js/template-customizer.js"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="../../assets/js/front-config.js"></script>
  </head>

  <body>
    <script src="../../assets/vendor/js/dropdown-hover.js"></script>
    <script src="../../assets/vendor/js/mega-dropdown.js"></script>

    <!-- Navbar: Start -->
    <nav class="layout-navbar container shadow-none py-0">
      <div class="navbar navbar-expand-lg landing-navbar border-top-0 px-4 px-md-8">
        <!-- Menu logo wrapper: Start -->
        <div class="navbar-brand app-brand demo d-flex py-0 py-lg-2 me-6">
          <!-- Mobile menu toggle: Start-->
          <button
            class="navbar-toggler border-0 px-0 me-2"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <i class="tf-icons ri-menu-fill ri-24px align-middle"></i>
          </button>
          <!-- Mobile menu toggle: End-->
          <a href="{{ url('/') }}" class="app-brand-link">
            <span class="app-brand-logo">
              <span style="color: #666cff">
                <img src="../assets/images/LLDIKTI-LOGOrev1-1-768x142.png" height="28">
              </span>
            </span>
          </a>
        </div>
        <!-- Menu logo wrapper: End -->
        <!-- Menu wrapper: Start -->
        <div class="collapse navbar-collapse landing-nav-menu" id="navbarSupportedContent">
          <button
            class="navbar-toggler border-0 text-heading position-absolute end-0 top-0 scaleX-n1-rtl"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <i class="tf-icons ri-close-fill"></i>
          </button>
          <ul class="navbar-nav me-auto p-4 p-lg-0">
            <li class="nav-item">
              <a class="nav-link fw-medium" aria-current="page" href="{{ url('/') }}">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link fw-medium" href="#landingKegiatan">Kegiatan</a>
            </li>
            <li class="nav-item">
              <a class="nav-link fw-medium text-nowrap" href="#landingContact">Saran & Masukan</a>
            </li>
          </ul>
        </div>
        <div class="landing-menu-overlay d-lg-none"></div>
        <!-- Menu wrapper: End -->
        <!-- Toolbar: Start -->
        <ul class="navbar-nav flex-row align-items-center ms-auto">
          <!-- Style Switcher -->
          <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
            <a
              class="nav-link btn btn-text-secondary rounded-pill btn-icon dropdown-toggle hide-arrow me-sm-4"
              href="javascript:void(0);"
              data-bs-toggle="dropdown">
              <i class="ri-22px text-heading"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                  <span class="align-middle"><i class="ri-sun-line ri-22px me-3"></i>Light</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                  <span class="align-middle"><i class="ri-moon-clear-line ri-22px me-3"></i>Dark</span>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                  <span class="align-middle"><i class="ri-computer-line ri-22px me-3"></i>System</span>
                </a>
              </li>
            </ul>
          </li>
          <!-- / Style Switcher-->

          <!-- navbar button: Start -->
          <li>
            <a
              href="{{ url('login') }}"
              class="btn btn-primary px-2 px-sm-4 px-lg-2 px-xl-4"
              ><span class="tf-icons ri-user-line me-md-1"></span
              ><span class="d-none d-md-block">Login</span></a
            >
          </li>
          <!-- navbar button: End -->
        </ul>
        <!-- Toolbar: End -->
      </div>
    </nav>
    <!-- Navbar: End -->

    <!-- Sections:Start -->

    <div data-bs-spy="scroll" class="scrollspy-example">
      <!-- Hero: Start -->
      <section id="landingHero" class="section-py landing-hero position-relative">
        <img
          src="../../assets/img/front-pages/backgrounds/hero-bg-light.png"
          alt="hero background"
          class="position-absolute top-0 start-0 w-100 h-100 z-n1"
          data-speed="1"
          data-app-light-img="front-pages/backgrounds/hero-bg-light.png"
          data-app-dark-img="front-pages/backgrounds/hero-bg-dark.png" />
        <div class="container">
          <div class="hero-text-box text-center">
            <h3 class="text-primary hero-title fs-2">PROGRAM PERGURUAN TINGGI MEMBANGUN DESA DI NUSANTARA</h3>
            <h2 class="h6 mb-8">
            LLDIKTI WILAYAH IV TAHUN 2024.<br />“Serentak Bergerak Mewujudkan MBKM Mandiri"
            </h2>
          </div>
          <div class="position-relative hero-animation-img">
            <a href="#">
              <div class="hero-dashboard-img text-center">
              <img
                  src="../../assets/images/hero-dashboard-light.png"
                  alt="hero dashboard"
                  class="animation-img"
                  data-speed="2"
                  data-app-light-img="../../assets/images/hero-dashboard-light.png?"
                  data-app-dark-img="../../assets/images/hero-dashboard-light.png?" />
              </div>
              <div class="position-absolute hero-elements-img">
              <img
                  src="../../assets/images/hero-dashboard-light.png"
                  alt="hero dashboard"
                  class="animation-img"
                  data-speed="2"
                  data-app-light-img="../../assets/images/hero-dashboard-light.png?"
                  data-app-dark-img="../../assets/images/hero-dashboard-light.png?" />
              </div>
            </a>
          </div>
        </div>
      </section>
      <!-- Hero: End -->

      <!-- FAQ: Start -->
      <section id="landingKegiatan" class="section-py bg-body landing-faq">
        <div class="container bg-icon-right">
          <img
            src="../../assets/img/front-pages/icons/bg-right-icon-light.png"
            alt="section icon"
            class="position-absolute top-0 end-0"
            data-speed="1"
            data-app-light-img="front-pages/icons/bg-right-icon-light.png"
            data-app-dark-img="front-pages/icons/bg-right-icon-dark.png" />
          <h6 class="text-center d-flex justify-content-center align-items-center mb-6">
            <img
              src="../../assets/img/front-pages/icons/section-tilte-icon.png"
              alt="section title icon"
              class="me-3" />
            <span class="text-uppercase">Kegiatan</span>
          </h6>
          <h5 class="text-center mb-2">Manfaat <span class="display-5 fs-4 fw-bold"> Kegiatan</span></h5>
          <p class="text-center fw-medium mb-4 mb-md-12 pb-4">
            Manfaat kegiatan bagi Mahasiswa, Masyarakat, Perguruan Tinggi, Dosen dan Pemerintah
          </p>
          <div class="row gy-5">
            <div class="col-lg-5">
              <div class="text-center">
                <img
                  src="../../assets/img/front-pages/landing-page/sitting-girl-with-laptop.png
          "
                  alt="sitting girl with laptop"
                  class="faq-image scaleX-n1-rtl" />
              </div>
            </div>
            <div class="col-lg-7">
              <div class="accordion" id="accordionFront">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="head-One">
                    <button
                      type="button"
                      class="accordion-button collapsed"
                      data-bs-toggle="collapse"
                      data-bs-target="#accordionOne"
                      aria-expanded="true"
                      aria-controls="accordionOne">
                      Mahasiswa
                    </button>
                  </h2>

                  <div
                    id="accordionOne"
                    class="accordion-collapse collapse"
                    data-bs-parent="#accordionFront"
                    aria-labelledby="accordionOne">
                    <div class="accordion-body">
                      <ul>
                        <li> Memperdalam pengertian, penghayatan, dan pengalaman mahasiswa tentang: Cara berfikir dan bekerja interdispliner dan lintas sektoral.</li>
                        <li> Mendewasakan alam pikiran mahasiswa dalam setiap penelaahan dan pemecahan masalah yang ada di masyarakat secara pragmatis dan</li>
                        <li> Membentuk sikap dan rasa cinta, kepedulian sosial, dan tanggung jawab mahasiswa terhadap kemajuan masyarakat.</li>
                        <li> Memberikan keterampilan kepada mahasiswa untuk melaksanakan program-program pengembangan dan pembangunaan.</li>
                        <li> Membina mahasiswa agar menjadi seorang motivator dan problem solver.</li>
                        <li> Memberikan pengalaman dan keterampilan kepada mahasiswa sebagai kader pembangunan</li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="accordion-item previous-active">
                  <h2 class="accordion-header" id="head-Two">
                    <button
                      type="button"
                      class="accordion-button collapsed"
                      data-bs-toggle="collapse"
                      data-bs-target="#accordionTwo"
                      aria-expanded="false"
                      aria-controls="accordionTwo">
                      Masyarakat
                    </button>
                  </h2>
                  <div
                    id="accordionTwo"
                    class="accordion-collapse collapse"
                    aria-labelledby="accordionTwo"
                    data-bs-parent="#accordionFront">
                    <div class="accordion-body">
                      <ul>
                        <li> Mendapatkan pendampingan untuk merencanakan, melaksanakan program pembangunan serta memecahkan berbagai masalah yangada di masyarakat.</li>
                        <li> Meningkatkan kemampuan berpikir, bersikap, dan bertindak untuk mendukung program pembangunaan.</li>
                        <li> Memperoleh pembaharuan-pembaharuan yang diperlukan dalam pembangunan daerah.</li>
                        <li> Membentuk kader-kader pembangunan di masyarakat sehingga terjamin kesinambungan pembangunan.</li>
                      </ul>

                    </div>
                  </div>
                </div>
                <div class="accordion-item active">
                  <h2 class="accordion-header" id="head-Three">
                    <button
                      type="button"
                      class="accordion-button"
                      data-bs-toggle="collapse"
                      data-bs-target="#accordionThree"
                      aria-expanded="true"
                      aria-controls="accordionThree">
                      Perguruan Tinggi
                    </button>
                  </h2>
                  <div
                    id="accordionThree"
                    class="accordion-collapse collapse show"
                    aria-labelledby="accordionThree"
                    data-bs-parent="#accordionFront">
                    <div class="accordion-body">
                      <ul>
                        <li> Implementasi Kebijakan Merdeka Belajar Kampus Merdeka Mandiri oleh Perguruan Tinggi</li>
                        <li> Implementasi Konversi Satuan Kredit Semester dari Pembelajaran di Luar Kampus</li>
                        <li> Perguruan Tinggi dapat menjalin kerjasama dengan instansi pemerintah atau lembaga lainnya dalam pengembangan IPTEK</li>
                        <li> Perguruan Tinggi dapat mengembangkan IPTEK yang lebih bermanfaat dalam pengelolaan dan penyelesaian berbagai masalah di masyarakat.</li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="head-Four">
                    <button
                      type="button"
                      class="accordion-button collapsed"
                      data-bs-toggle="collapse"
                      data-bs-target="#accordionFour"
                      aria-expanded="false"
                      aria-controls="accordionFour">
                      Dosen
                    </button>
                  </h2>
                  <div
                    id="accordionFour"
                    class="accordion-collapse collapse"
                    aria-labelledby="accordionFour"
                    data-bs-parent="#accordionFront">
                    <div class="accordion-body">
                      <ul>
                        <li> Aplikasi keilmuan</li>
                        <li> Implementasi IKU Dosen Berkegiatan di luar Kampus</li>
                        <li> Dukungan terhadap MBKM sebagai dosen pendamping mahasiswa yang akan melaksanakan MBKM</li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="accordion-item">
                  <h2 class="accordion-header" id="head-Five">
                    <button
                      type="button"
                      class="accordion-button collapsed"
                      data-bs-toggle="collapse"
                      data-bs-target="#accordionFive"
                      aria-expanded="false"
                      aria-controls="accordionFive">
                      Pemerintah
                    </button>
                  </h2>
                  <div
                    id="accordionFive"
                    class="accordion-collapse collapse"
                    aria-labelledby="accordionFive"
                    data-bs-parent="#accordionFront">
                    <div class="accordion-body">
                      <ul>
                        <li> Menjembatani kebijakan pemerintah dalam merencanakan dan melaksanakan pembangunan daerah.</li>
                        <li> Mendapatkan bantuan pemikiran dan tenaga untuk merencanakan serta mensosialisasikan program pemerintah daerah</li>
                        <li> Memperoleh pembaharuan-pembaharuan yang diperlukan dalam pemberdayaan daerah.</li>
                        <li> Membentuk kader-kader pemberdayaan masyarakat.</li>
                        <li> Menjalin kesinergian Perguruan Tinggi dengan Pemerintah Daerah dalam pelaksanaan Tri Dharma Perguruan Tinggi</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- FAQ: End -->



      <!-- Contact Us: Start -->
      <section id="landingContact" class="section-py bg-body landing-contact">
        <div class="container bg-icon-left position-relative">
          <img
            src="../../assets/img/front-pages/icons/bg-left-icon-light.png"
            alt="section icon"
            class="position-absolute top-0 start-0"
            data-speed="1"
            data-app-light-img="front-pages/icons/bg-left-icon-light.png"
            data-app-dark-img="front-pages/icons/bg-left-icon-dark.png" />
          <h6 class="text-center d-flex justify-content-center align-items-center mb-6">
            <img
              src="../../assets/img/front-pages/icons/section-tilte-icon.png"
              alt="section title icon"
              class="me-3" />
            <span class="text-uppercase">Saran dan Masukan</span>
          </h6>
          <h5 class="text-center mb-2"><span class="display-5 fs-4 fw-bold">Serentak Bergerak</span> Mewujudkan MBKM Mandiri</h5>
          <p class="text-center fw-medium mb-4 mb-md-12 pb-3">Apakah ada saran dan masukan? silahkan kirim pesan</p>
          <div class="row gy-6">
            <div class="col-lg-5">
              <div class="card h-100">
                <div class="bg-primary rounded-4 text-white card-body p-8">
                  <p class="fw-medium mb-1_5 tagline">Let’s contact with us</p>
                  <h4 class="text-white mb-5 title">Share your ideas.</h4>
                  <img
                    src="../../assets/img/front-pages/landing-page/let’s-contact.png"
                    alt="let’s contact"
                    class="w-100 mb-5" />
                  <p class="mb-0 description">
                    Saran, Masukan.
                  </p>
                </div>
              </div>
            </div>
            <div class="col-lg-7">
              <div class="card">
                <div class="card-body">
                  <h5 class="mb-6">Share your ideas</h5>
                  <form id="form-saran" method="post" action="{{ url('saran/insert') }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-5">
                      <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                          <input type="text" name="nama" class="form-control" id="basic-default-fullname" placeholder="Nama Lengkap" />
                          <label for="basic-default-fullname">Full name</label>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                          <input
                            type="email"
                            name="email"
                            class="form-control"
                            id="basic-default-email"
                            placeholder="email@gmail.com" />
                          <label for="basic-default-email">Email address</label>
                        </div>
                      </div>

                      <div class="col-12">
                        <div class="form-floating form-floating-outline">
                          <textarea
                            name="saran"
                            class="form-control h-px-250"
                            placeholder="Message"
                            aria-label="Message"
                            id="basic-default-message"></textarea>
                          <label for="basic-default-message">Message</label>
                        </div>
                      </div>
                    </div>
                    <button type="submit" id="btnSubmit_form-saran" class="btn btn-primary mt-5">Send inquiry</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- Contact Us: End -->
    </div>

    <!-- / Sections:End -->

    <!-- Footer: Start -->
    <footer class="landing-footer">
      <div class="footer-top position-relative overflow-hidden">
        <img src="../../assets/img/front-pages/backgrounds/footer-bg.png" alt="footer bg" class="footer-bg banner-bg-img" />
        <div class="container position-relative">
          <div class="row gx-0 gy-7 gx-sm-6 gx-lg-12">
            <div class="col-lg-5">
              <a href="landing-page.html" class="app-brand-link mb-6">
                <span class="app-brand-logo demo me-2">
                  <span style="color: #666cff">
                    <img src="../assets/images/lldikti4_logo.png" height="35">
                  </span>
                </span>
              </a>
              <p class="footer-text footer-logo-description mb-6">
              PROGRAM PERGURUAN TINGGI MEMBANGUN DESA DI NUSANTARA LLDIKTI WILAYAH IV TAHUN 2024 <span class="text-warning">“Serentak Bergerak Mewujudkan MBKM Mandiri”</span>
              <hr class="text-dark">              
                Email: informasi@lldikti4.id<br>
                Jalan Penghulu H. Hasan Mustofa No. 38 Bandung 40124  
              </p>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
              <h6 class="footer-title mb-4 mb-lg-6">Link Terkait</h6>
              <ul class="list-unstyled mb-0">
                <li class="mb-4">
                  <a href="https://lldikti4.kemdikbud.go.id" target="_blank" class="footer-link">LLDIKTI IV</a>
                </li>
                <li class="mb-4">
                  <a href="https://mbkm.lldikti4.id" target="_blank" class="footer-link">MBKM LLDIKTI IV 2023</a>
                </li>
                <li class="mb-4">
                  <a href="https://pusatinformasi.kampusmerdeka.kemdikbud.go.id" target="_blank" class="footer-link">Pusat Informasi Kampus Merdeka</a>
                </li>
                <li class="mb-4">
                  <a href="https://kampusmerdeka.kemdikbud.go.id/" target="_blank" class="footer-link">Kampus Merdeka</a>
                </li>
               
              </ul>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
              <h6 class="footer-title mb-4 mb-lg-6">Laman</h6>
              <ul class="list-unstyled mb-0">
                <li class="mb-4">
                  <a href="{{ url('faq') }}" class="footer-link">FAQ</a>
                </li>
                <li>
                  <a href="{{ url('login') }}" class="footer-link">Login</a>
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
            <span class="footer-text">© 2024, Data Informasi & Pembiayaan Pendidikan LLDIKTI Wilayah IV
          </div>
          <div>
            <a href="https://m.facebook.com/LLDIKTIWILAYAH4/?tsid=0.24115179413463506&source=result" class="footer-link me-4" target="_blank"><i class="ri-facebook-circle-fill"></i></a>
            <a href="https://twitter.com/lldiktiwilayah4?s=09" class="footer-link me-4" target="_blank"><i class="ri-twitter-fill"></i></a>
            <a href="https://instagram.com/lldiktiwilayah4?utm_medium=copy_link" class="footer-link" target="_blank"><i class="ri-instagram-line"></i></a>
          </div>
        </div>
      </div>
    </footer>
    <!-- Footer: End -->
    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="../../assets/vendor/libs/nouislider/nouislider.js"></script>
    <script src="../../assets/vendor/libs/swiper/swiper.js"></script>

    <!-- Main JS -->
    <script src="../../assets/js/front-main.js"></script>
    <script src="../../assets/vendor/libs/toastr/toastr.js"></script>

    <!-- Page JS -->
    <script src="../../assets/js/front-page-landing.js"></script>
  </body>
</html>
<script>
$(function(){
  $("body").on("submit","#form-saran",function(e){
        e.preventDefault();     
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();
        var dString = $(this).serialize();
        $.ajax({
            type:'post',
            url:action,
            data:dString,
            beforeSend:function(){
                $("#btnSubmit_"+id+"").prop("disabled",true);
                $("#btnSubmit_"+id+"").html("<span class='spinner-border' role='status' aria-hidden='true'></span> Loading...");			
            },
            complete:function(){
                $("#btnSubmit_"+id+"").prop("disabled",false);
                $("#btnSubmit_"+id+"").html(btnHtml);	
            },
            success:function(ret){
                if(ret.success == true){		
                    toastr.success(ret.message)			
                }else{                    
                    toastr.warning(ret.message)
                    if (ret.hasOwnProperty('errors')) {
                                // Ada kesalahan validasi
                        var errors = ret.errors;

                        // Menghapus pesan error sebelumnya
                        $('.errors-message').remove();

                        // Menampilkan pesan error pada setiap field
                        $.each(errors, function(key, value) {
                            var inputField = $('[name="' + key + '"]');
                            inputField.after('<span class="errors-message text-danger">' + value[0] + '</span>');
                            // Menambahkan event listener untuk menghapus pesan error saat field mendapatkan fokus
                            inputField.on('focus', function(){
                                    $(this).siblings('.errors-message').remove();
                                });
                        });
                    }
                }
            },
            error:function(xhr,ajaxOptions,thrownError){
                console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
            }			
            
        })
    })
  
})
</script>
