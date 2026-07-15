<div class="container-xxl flex-grow-1 container-p-y">
    @php
        $isDpl = Auth::user()->role == 'dpl';
        $isMahasiswa = Auth::user()->role == 'mahasiswa';
        $photoUploadUrl = $isMahasiswa ? url('mhsprofile/uploadpoto') : url('profile/uploadpoto');
        $photoRoute = $isMahasiswa ? route('mhsprofile.getPoto') : route('profile.getPoto');
        $updateUrl = $isMahasiswa ? url('mhsprofile/update') : url('profile/update');
        $displayName = $isMahasiswa ? ($mahasiswa->nama ?? $profile->name) : $profile->name;
    @endphp
    <!-- Header -->
    <div class="row">
      <div class="col-12">
          <div class="card mb-6">
          <div class="user-profile-header-banner">
              <img src="../../assets/img/pages/profile-banner.png" alt="Banner image" class="rounded-top" />
          </div>
          <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
              <div class="flex-shrink-0 mt-n2 mx-sm-0 mx-auto">
              <x-btn-modal url="{{ $photoUploadUrl }}" class="modalButton" title="Upload Profile">
              <img id="showimageprofile"
              src="{{ $photoRoute }}?rand={{ time() }}"
              alt="user image"
              class="d-block h-auto ms-0 ms-sm-5 rounded user-profile-img" />

              </x-btn-modal>
              
              </div>
              <div class="flex-grow-1 mt-4 mt-sm-12">
              <div
                  class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                  <div class="user-profile-info">
                  <h4 class="mb-2">{{ $displayName }} [{{ $profile->email }}]</h4>
                  <ul  class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                      @if ($isDpl || $isMahasiswa)
                            <li class="list-inline-item">
                                @isset($dpl){{ $dpl->sp->nm_lemb ?? '-' }}@endisset
                                @isset($mahasiswa){{ $mahasiswa->prodi ?? '-' }}@endisset
                            </li>
                      @endif
                      <li class="list-inline-item">
                        <i class="ri-user-line me-2 ri-24px"></i><span class="fw-medium">Level : {{ $profile->role }}</span>
                      </li>
                      <li class="list-inline-item">
                      <i class="ri-calendar-line me-2 ri-24px"></i>
                      <span class="fw-medium"> Bergabung {{ \Carbon\Carbon::parse($profile->created_at)->format('Y-m-d H:i:s') }}</span>
                      </li>
                  </ul>
                  </div>
                  <a href="javascript:void(0)" class="btn btn-primary">
                  <i class="ri-user-follow-line ri-16px me-2"></i>Terakhir Login {{ $profile->last_login }}
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
                  <span class="align-middle">Profil</span>
                </button>
              </li>
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#updatepassword">
                  <i class="ri-lock-line me-2"></i>
                  <span class="align-middle">Ubah Kata Sandi</span>
                </button>
              </li>
              @if($isMahasiswa)
              <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#menucepat">
                  <i class="ri-links-line me-2"></i>
                  <span class="align-middle">Menu Cepat</span>
                </button>
              </li>
              @endif
            </ul>
            
          </div>
        </div>
        <!-- /Navigation -->

        <!-- FAQ's -->
        <div class="col-lg-9 col-md-8 col-12">
          <div class="tab-content p-0">
            <div class="tab-pane fade show active" id="profile" role="tabpanel">
              <x-page-header icon="ri-bank-card-line" title="Profil" subtitle="Informasi Akun" />

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
                      {{ $displayName }}
                    </button>
                  </h2>

                  <div id="accordionPayment-1" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                    @if(Auth::user()->role == "admin")
                        <form method="post" id="form-update" action="{{ $updateUrl }}">
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
                            <x-btn-save formId="form-update" class="btn btn-sm btn-primary rounded-pill">Save changes</x-btn-save>
                        </form>
                    @elseif($isMahasiswa)
                        <form method="post" id="form-update" action="{{ $updateUrl }}">
                            @csrf
                            @method('PUT')
                            <!-- General information -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-merge mb-6">
                                        <span id="basic-icon-default-fullname2" class="input-group-text"><i class="ri-user-line"></i></span>
                                        <div class="form-floating form-floating-outline">
                                            <input class="form-control" type="text" name="nama" value="{{ $mahasiswa->nama ?? '-' }}">
                                            <label class="form-control-label">Nama Lengkap</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group form-floating form-floating-outline mb-6">
                                    <input class="form-control" type="text" name="nim" value="{{ $mahasiswa->nim ?? '-' }}">
                                    <label class="form-control-label">NIM</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <div class="form-group form-floating form-floating-outline mb-6">
                                    <select class="form-control" id="select2" name="kodept" data-toggle="select">
                                        @if($sp)
                                            @foreach($sp as $row)
                                                <option value="{{$row->npsn}}" @if($mahasiswa->kodept == $row->npsn) selected @endif>{{$row->nm_lemb}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <label class="form-control-label">Perguruan Tinggi</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="form-group form-floating form-floating-outline mb-6">
                                    <input class="form-control" type="text" name="prodi" value="{{ $mahasiswa->prodi }}">
                                    <label class="form-control-label">Prodi</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group form-floating form-floating-outline mb-6">
                                    <input class="form-control" type="text" name="tahun_masuk" value="{{ $mahasiswa->tahun_masuk }}">
                                    <label class="form-control-label">Tahun Masuk</label>
                                    </div>
                                </div>
                            </div>
                            <div class="input-group input-group-merge mb-6">
                                <span id="basic-icon-default-phone2" class="input-group-text"><i class="ri-phone-fill"></i></span>
                                <div class="form-floating form-floating-outline">
                                <input type="text" name="phone" value="{{ $mahasiswa->phone }}" id="basic-icon-default-phone" class="form-control phone-mask" placeholder="658 799 8941" aria-label="658 799 8941" aria-describedby="basic-icon-default-phone2">
                                <label for="basic-icon-default-phone">Phone No</label>
                                </div>
                            </div>
                            <hr />
                            
                            <!-- Save changes buttons -->
                            <x-btn-save formId="form-update" class="btn btn-sm btn-primary rounded-pill"><i class="ri-save-2-fill pe-1"></i>Save changes</x-btn-save>
                        </form>
                    @else
                        @if(!$dpl)
                            <div class="alert alert-info">
                              <span>Penting</span>
                              <span class="alert-content">Sebelum melakukan aktifitas lain mohon untuk mengisi kelengkapan profil!</span>
                            </div>
                        @endif
                        <form method="post" id="form-update" action="{{ $updateUrl }}">
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
                            <x-btn-save formId="form-update" class="btn btn-sm btn-primary rounded-pill">Save changes</x-btn-save>
                        </form>
                    @endif   
                    </div>
                  </div>
                </div>

              </div>
            </div>
            <div class="tab-pane fade" id="updatepassword" role="tabpanel">
              <x-page-header icon="ri-lock-line" title="Kata Sandi" subtitle="Kelola Kata Sandi" />
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
                        <x-btn-save formId="form-updatepassword" class="btn btn-primary" name="kirim">Simpan</x-btn-save>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            @if($isMahasiswa)
            <div class="tab-pane fade" id="menucepat" role="tabpanel">
              <x-page-header icon="ri-links-line" title="Menu Cepat" subtitle="Akses cepat ke aktifitas KKN." />
              <div class="card">
                <div class="list-group list-group-flush">
                <div class="list-group-item">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded-3 bg-label-info"><i class="ri-check-line text-info ri-24px"></i></span>
                        </div>
                        <div class="media-body ml-3">
                            <a href="{{ url('logkegiatan') }}" class="stretched-link h6 mb-1">Log Harian</a>
                            <p class="mb-0 text-sm">Informasi log harian</p>
                        </div>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded-3 bg-label-info"><i class="ri-news-line text-info ri-24px"></i></span>
                        </div>
                        <div class="media-body ml-3">
                            <a href="{{ url('logkehadiran') }}" class="stretched-link h6 mb-1">Kehadiran</a>
                            <p class="mb-0 text-sm">Informasi kehadiran</p>
                        </div>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded-3 bg-label-info"><i class="ri-information-line text-info ri-24px"></i></span>
                        </div>
                        <div class="media-body ml-3">
                            <a href="{{ url('logbulanan') }}" class="stretched-link h6 mb-1">Log Bulanan</a>
                            <p class="mb-0 text-sm">Informasi Log Bulanan</p>
                        </div>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded-3 bg-label-info"><i class="ri-information-line text-info ri-24px"></i></span>
                        </div>
                        <div class="media-body ml-3">
                            <x-btn-modal url="{{ url('mhsprofile/formlokasi') }}" class="modalButton stretched-link h6 mb-1" title="Set lokasi">Lokasi Kegiatan</x-btn-modal>
                            <p class="mb-0 text-sm">{{ $mahasiswa->lokasi->desa->desa ?? 'Belum di set' }}</p>
                        </div>
                    </div>
                </div>
                </div>
              </div>
            </div>
            @endif
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

    $("body").on("change","#form-file-upload",function(e){
        e.preventDefault();
        var formData = new FormData($(this)[0]);
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();		
		  $.ajax({
			  url: action,
              dataType:'json',
              type:'post',
			  data: formData,
			  processData: false, // important
			  contentType: false, // important
			  beforeSend:function(){					
				  $("#btnSubmit_"+id+"").prop("disabled",true);
				  $("#btnSubmit_"+id+"").html("<span class='spinner-grow spinner-grow-sm' role='status'></span> loading...");			
			  },
			  complete:function(){
				  $("#btnSubmit_"+id+"").prop("disabled",false);
				  $("#btnSubmit_"+id+"").html(btnHtml);	
			  },
			  success: function(ret) {
				    if(ret.success == true){
              toastr.success(ret.message)		
            }else{
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
              toastr.warning(ret.message)	
            }
			  },
			  error:function(xhr,ajaxOptions,thrownError){
				  console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);
                  toastr.error(thrownError)				
			  }	

		  });
	});
    $("body").on("submit","#form-update,#form-updatepassword",function(){       
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
                $("#btnSubmit_"+id+"").html("<span class='spinner-grow spinner-grow-sm' role='status' aria-hidden='true'></span> Loading...");			
            },
            complete:function(){
                $("#btnSubmit_"+id+"").prop("disabled",false);
                $("#btnSubmit_"+id+"").html(btnHtml);	
            },
            success:function(ret){
                if(ret.success == true){
                  toastr.success(ret.message)		
                }else{
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
                    toastr.warning(ret.message)	
                }
            },
            error:function(xhr,ajaxOptions,thrownError){
                console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
            }			
            
        });
        return false;
    });
})
</script>
