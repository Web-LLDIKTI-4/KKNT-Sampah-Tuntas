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
        <a href="{{ url('/home') }}" class="app-brand-link">
        <span class="app-brand-logo">
            <span style="color: #666cff">
            <img src="../../assets/images/LLDIKTI-LOGOrev1-1-768x142.png" height="28">
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
            <a class="nav-link fw-medium" aria-current="page" href="{{ url('/home') }}">Home</a>
        </li>
        
        <li class="nav-item mega-dropdown">
            <a
            href="javascript:void(0);"
            class="nav-link dropdown-toggle navbar-ex-14-mega-dropdown mega-dropdown fw-medium"
            aria-expanded="false"
            data-bs-toggle="mega-dropdown"
            data-trigger="hover">
            <span data-i18n="Pages">Kelola Data</span>
            </a>
            <div class="dropdown-menu p-4 p-lg-6">
            <div class="row gy-4">
                <div class="col-12 col-lg">
                <div class="h6 d-flex align-items-center mb-2 mb-lg-4">
                    <div class="avatar avatar-sm flex-shrink-0 me-2">
                    <span class="avatar-initial rounded bg-label-primary"><i class="ri-layout-grid-line"></i></span>
                    </div>
                    <span class="ps-1">Setting Data</span>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link mega-dropdown-link d-flex align-items-center" href="{{ url('perguruantinggi') }}">
                            <i class="menu-icon tf-icons ri-circle-line me-2"></i>
                            <span data-i18n="Pricing">Satuan Pendidikan</span>
                        </a>
                    </li>                   
                </ul>
                </div>
                <div class="col-12 col-lg">
                <div class="h6 d-flex align-items-center mb-2 mb-lg-4">
                    <div class="avatar avatar-sm flex-shrink-0 me-2">
                    <span class="avatar-initial rounded bg-label-primary"><i class="ri-lock-unlock-line"></i></span>
                    </div>
                    <span class="ps-1">Kelola Laporan Mahasiswa</span>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link mega-dropdown-link d-flex align-items-center" href="{{ url('admlogkegiatan') }}">
                            <i class="menu-icon tf-icons ri-circle-line me-2"></i> Data Kegiatan Mahasiswa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mega-dropdown-link d-flex align-items-center" href="{{ url('admlogbulanan') }}">
                            <i class="menu-icon tf-icons ri-circle-line me-2"></i> Data Log Bulanan Mahasiswa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mega-dropdown-link d-flex align-items-center" href="{{ url('admlogkehadiran') }}">
                            <i class="menu-icon tf-icons ri-circle-line me-2"></i> Data Kehadiran Mahasiswa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mega-dropdown-link d-flex align-items-center" href="{{ url('lapcapaiankpi') }}">
                            <i class="menu-icon tf-icons ri-circle-line me-2"></i> Data capaian key performance indicator
                        </a>
                    </li>
                </ul>
                </div>
                <div class="col-12 col-lg">
                <div class="h6 d-flex align-items-center mb-2 mb-lg-4">
                    <div class="avatar avatar-sm flex-shrink-0 me-2">
                    <span class="avatar-initial rounded bg-label-primary"><i class="ri-image-fill"></i></span>
                    </div>
                    <span class="ps-1">Kelola Laporan DPL</span>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link mega-dropdown-link d-flex align-items-center" href="{{ url('dpllaporan') }}">
                            <i class="menu-icon tf-icons ri-circle-line me-2"></i> Data Log Bulanan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mega-dropdown-link d-flex align-items-center" href="{{ url('dpllaptugasakhir') }}">
                            <i class="menu-icon tf-icons ri-circle-line me-2"></i> Data Tugas Akhir KKN
                        </a>
                    </li>                    
                </ul>
                </div>
                <div class="col-lg-4 d-none d-lg-block">
                    <div class="h6 d-flex align-items-center mb-2 mb-lg-4">
                        <div class="avatar avatar-sm flex-shrink-0 me-2">
                        <span class="avatar-initial rounded bg-label-primary"><i class="ri-lock-unlock-line"></i></span>
                        </div>
                        <span class="ps-1">Data Lain</span>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link mega-dropdown-link d-flex align-items-center" href="{{ url('dplmentoring') }}">
                                <i class="menu-icon tf-icons ri-circle-line me-2"></i> Data Mahasiswa Mentor
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link mega-dropdown-link d-flex align-items-center" href="{{ url('dplkonversinilai') }}">
                                <i class="menu-icon tf-icons ri-circle-line me-2"></i> Data Nilai Structure form
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link mega-dropdown-link d-flex align-items-center" href="{{ url('dplfreeform') }}">
                                <i class="menu-icon tf-icons ri-circle-line me-2"></i> Data Nilai Free Form
                            </a>
                        </li>               
                   
                    </ul>
                </div>
            </div>
            </div>
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
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="true">
                <div class="avatar avatar-online">
                    <img src="{{ route('profile.getPoto') }}?rand={{ time() }}" alt="" class="rounded-circle">
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end mt-3" data-bs-popper="static">
                <li>
                    <a class="dropdown-item waves-effect" href="{{ url('profile') }}">
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-2">
                        <div class="avatar avatar-online">
                            <img src="{{ route('profile.getPoto') }}?rand={{ time() }}" alt="" class="rounded-circle">
                        </div>
                        </div>
                        <div class="flex-grow-1">
                        <span class="fw-medium d-block small">{{ Auth::user()->name}}</span>
                        <small class="text-muted">{{ Auth::user()->role}}</small>
                        </div>
                    </div>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider"></div>
                </li>
                <li>
                    <a class="dropdown-item waves-effect" href="{{ url('profile') }}">
                    <i class="ri-user-3-line ri-22px me-3"></i><span class="align-middle">My Profile</span>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider"></div>
                </li>
                <li>
                    <div class="d-grid px-4 pt-2 pb-1">
                    <a class="btn btn-sm btn-danger d-flex waves-effect waves-light" href="{{ url('logout') }}">
                        <small class="align-middle">Logout</small>
                        <i class="ri-logout-box-r-line ms-2 ri-16px"></i>
                    </a>
                    </div>
                </li>
            </ul>
        </li>
        <!-- navbar button: End -->
    </ul>
    <!-- Toolbar: End -->
    </div>
</nav>
<!-- Navbar: End -->