<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header -->
    <div class="row">
      <div class="col-12">
          <div class="card mb-6">
          <div class="user-profile-header-banner">
              <img src="../../assets/img/pages/profile-banner.png" alt="Banner image" class="rounded-top" />
          </div>
          <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
              <div class="flex-shrink-0 mt-n2 mx-sm-0 mx-auto">
              <a class="modalButton" data-bs-toggle="modal" href="#modalku" data-src="{{ url('profile/uploadpoto') }}" title="Upload Profile">
              <img id="showimageprofile"
              src="{{ route('profile.getPoto') }}?rand={{ time() }}"
              alt="user image"
              class="d-block h-auto ms-0 ms-sm-5 rounded user-profile-img" />

              </a>
              </div>
              <div class="flex-grow-1 mt-4 mt-sm-12">
              <div
                  class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                  <div class="user-profile-info">
                  <h4 class="mb-2">{{ $profile->name }} [{{ $profile->email }}]</h4>
                  <ul  class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                      <li class="list-inline-item">
                         {{ $dpl->sp->nm_lemb ?? '-' }}</span>
                      </li>
                      <li class="list-inline-item">
                        <i class="ri-user-line me-2 ri-24px"></i><span class="fw-medium">Level : {{ $profile->role }}</span>
                      </li>
                      <li class="list-inline-item">
                      <i class="ri-calendar-line me-2 ri-24px"></i>
                      <span class="fw-medium"> Joined {{ \Carbon\Carbon::parse($profile->created_at)->format('Y-m-d H:i:s') }}</span>
                      </li>
                  </ul>
                  </div>
                  <a href="javascript:void(0)" class="btn btn-primary">
                  <i class="ri-user-follow-line ri-16px me-2"></i>Last Login {{ $profile->last_login }}
                  </a>
              </div>
              </div>
          </div>
          </div>
      </div>
    </div>
    <!--/ Header -->
    <div class="container-xxl flex-grow-1 container-p-y">
      <div class="row">
        <!-- Navigation -->
        <div class="col-lg-3 col-md-4 col-12 mb-md-0 mb-4">
          <div class="d-flex justify-content-between flex-column nav-align-left mb-2 mb-md-0">
            <ul class="nav nav-pills flex-column flex-nowrap">
              <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile">
                  <i class="ri-bank-card-line me-2"></i>
                  <span class="align-middle">Profile</span>
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#updatepassword">
                  <i class="ri-lock-line me-2"></i>
                  <span class="align-middle">Update Password</span>
                </button>
              </li>
              
            </ul>
            
          </div>
        </div>
        <!-- /Navigation -->

        <!-- FAQ's -->
        <div class="col-lg-9 col-md-8 col-12">
          <div class="tab-content p-0">
            <div class="tab-pane fade show active" id="profile" role="tabpanel">
              <div class="d-flex mb-4 gap-4">
                <div class="avatar avatar-md">
                  <div class="avatar-initial bg-label-primary rounded-4">
                    <i class="ri-bank-card-line ri-30px"></i>
                  </div>
                </div>
                <div>
                  <h5 class="mb-0">
                    <span class="align-middle">Profile</span>
                  </h5>
                  <span>Informasi Akun</span>
                </div>
              </div>
              <div id="accordionPayment" class="accordion">
                <div class="accordion-item active">
                  <h2 class="accordion-header">
                    <button
                      class="accordion-button"
                      type="button"
                      data-bs-toggle="collapse"
                      aria-expanded="true"
                      data-bs-target="#accordionPayment-1"
                      aria-controls="accordionPayment-1">
                      {{ Auth::user()->name }}
                    </button>
                  </h2>

                  <div id="accordionPayment-1" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                    @if(Auth::user()->role == "admin")
                        <form method="post" id="form-update" action="{{ url('profile/update') }}">
                            @csrf
                            @method('PUT')
                            <!-- General information -->
                            <div class="row">
                                <div class="col">
                                    <div class="form-group form-floating form-floating-outline mb-6">
                                        <input class="form-control" type="text" name="nama" value="{{ $profile->name }}">
                                        <label class="form-control-label">Nama</label>
                                    </div>
                                </div>
                            </div>
                            <hr />
                            
                            <!-- Save changes buttons -->
                            <button type="submit" id="btnSubmit_form-update" class="btn btn-sm btn-primary rounded-pill">Save changes</button>
                        </form>
                    @else
                        @if(!$dpl)
                            <div class="alert alert-info">
                              <span>Penting</span>
                              <span class="alert-content">Sebelum melakukan aktifitas lain mohon untuk mengisi kelengkapan profil!</span>
                            </div>
                        @endif
                        <form method="post" id="form-update" action="{{ url('profile/update') }}">
                            @csrf
                            @method('PUT')
                            <!-- General information -->
                            <div class="row">
                            <div class="col-md-6">
                                <div class="form-group form-floating form-floating-outline mb-6">
                                <input class="form-control" type="text" name="nama" value="{{ $profile->name }}">
                                <label class="form-control-label">Nama</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group form-floating form-floating-outline mb-6">
                                <input class="form-control" type="text" name="nidn" value="{{ $dpl ? $dpl->nidn : '' }}">
                                <label class="form-control-label">NIDN</label>
                                </div>
                            </div>
                            </div>
                            <div class="row">
                            <div class="col">
                                <div class="form-group form-floating form-floating-outline mb-6">
                                <select class="form-control" id="select2" name="kodept" data-toggle="select">
                                    @if($sp)
                                        @foreach($sp as $row)
                                            @php
                                                $kodept = $dpl ? $dpl->kodept : '';
                                            @endphp
                                            <option value="{{$row->npsn}}" @if( $kodept == $row->npsn) selected @endif>{{$row->nm_lemb}}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <label class="form-control-label">Perguruan Tinggi</label>
                                </div>
                            </div>
                            </div>
                            <div class="row align-items-center">
                            <div class="col-md-12">
                                <div class="form-group form-floating form-floating-outline mb-6">
                                <input class="form-control" type="text" name="prodi" value="{{ $dpl ? $dpl->prodi : '' }}">
                                <label class="form-control-label">Prodi Homebase</label>
                                </div>
                            </div>
                            </div>
                            <div class="row">
                            <div class="col">
                                <div class="form-group form-floating form-floating-outline mb-6">
                                <input class="form-control" type="phone" name="phone" placeholder="nomor HP" value="{{ $dpl ? $dpl->phone : '' }}">
                                <label class="form-control-label">Kontak</label>
                                </div>
                            </div>
                            </div>
                            <hr />
                            
                            <!-- Save changes buttons -->
                            <button type="submit" id="btnSubmit_form-update" class="btn btn-sm btn-primary rounded-pill">Save changes</button>
                        </form>
                    @endif   
                    </div>
                  </div>
                </div>

              </div>
            </div>
            <div class="tab-pane fade" id="updatepassword" role="tabpanel">
              <div class="d-flex mb-4 gap-4 align-items-center">
                <div class="avatar avatar-md">
                  <span class="avatar-initial bg-label-primary rounded-4">
                    <i class="ri-lock-line ri-30px"></i>
                  </span>
                </div>
                <div>
                  <h5 class="mb-0">
                    <span class="align-middle">Setting Password</span>
                  </h5>
                  <span>Kelola Password.</span>
                </div>
              </div>
              <div id="accordionDelivery" class="accordion">
                <div class="accordion-item active">
                  <h2 class="accordion-header">
                    <button
                      class="accordion-button"
                      type="button"
                      data-bs-toggle="collapse"
                      aria-expanded="true"
                      data-bs-target="#accordionDelivery-1"
                      aria-controls="accordionDelivery-1">
                      Lengkapi Isian dengan benar
                    </button>
                  </h2>
                  <div id="accordionDelivery-1" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                      <form method="post" id="form-updatepassword" action="{{ url('setting/update') }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group form-floating form-floating-outline mb-6 mt-5">
                            <input type="text" name="plama" class="form-control">
                            <label for="plama">Masukan Password Lama</label>
                        </div>
                        <div class="row">
                          <div class="form-group form-floating form-floating-outline col mb-6">
                              <input type="password" name="pbaru" class="form-control">
                              <label for="pbaru">Password Baru</label>
                          </div>
                          <div class="form-group form-floating form-floating-outline col mb-6">
                          <input type="password" name="pbaruulangi" class="form-control">
                              <label for="pbaruulangi">Ulangi Password Baru</label>
                          </div>
                        </div>
                        <button type="submit" name="kirim" class="btn btn-primary" id="btnSubmit_form-updatepassword">Simpan</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- /FAQ's -->
      </div>
    
    </div>
    <!--/ Content -->
</div>
<script>
$(function(){
  $('#select2').select2({
       // theme: "",
    });
})
</script>