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

    <title>GRADASI4</title>

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

    <!-- Core CSS -->
    <link rel="stylesheet" href="../../assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="../../assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="../../assets/css/demo.css" />

    <!-- Helpers -->
    <script src="../../assets/vendor/js/helpers.js"></script>
    <script src="../../assets/js/front-config.js"></script>

    <style>
      body {
        min-height: 100vh;
        margin: 0;
        background-image: url('../../assets/images/bg-landing.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
      }

      .landing-wrap {
        min-height: 100vh;
        padding: 2rem 1rem;
      }

      .btn-landing-login {
        font-size: 1.1rem;
        padding: 0.9rem 2.6rem;
        border-radius: 10px;
        font-weight: 600;
        margin-left: 10rem;
      }

      @media (max-width: 576px) {
        .btn-landing-login {
          width: 100%;
          max-width: 320px;
          margin-left: 0;
        }
      }
    </style>
  </head>

  <body>
    <div class="container landing-wrap d-flex align-items-center justify-content-center">
      <a href="{{ url('login') }}" class="btn btn-warning btn-landing-login">
        <span>SILAHKAN LOGIN DISINI</span>
      </a>
    </div>

    <script src="../../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../../assets/vendor/libs/popper/popper.js"></script>
    <script src="../../assets/vendor/js/bootstrap.js"></script>
  </body>
</html>
