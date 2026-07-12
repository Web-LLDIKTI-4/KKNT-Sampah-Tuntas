               
<div class="row">
    <div class="col-lg-4 order-lg-2 mb-6">
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
                    <a href="#modalku" data-bs-toggle="modal" data-src="{{ url('mhsprofile/formlokasi') }}" title="Set lokasi" class="modalButton stretched-link h6 mb-1">Lokasi Kegiatan</a>
                    <p class="mb-0 text-sm">{{ $mahasiswa->lokasi->desa->desa ?? 'Belum di set' }}</p>
                </div>
            </div>
        </div>
        </div>
    </div>
    </div>
    <div class="col-lg-8 order-lg-1">
    <!-- Change avatar -->
    <div class="card hover-shadow-lg border-0 mb-6">
        <div class="card-body py-3">
            <div class="row row-grid align-items-center">
                <div class="col-lg-2">
                    <div class=" align-items-center">
                        <a class="modalButton" data-bs-toggle="modal" href="#modalku" data-src="{{ url('mhsprofile/uploadpoto') }}" title="Upload Profile">
                        <img id="showimageprofile"
                        src="{{ route('mhsprofile.getPoto') }}?rand={{ time() }}"
                        alt="user-avatar" class="d-block w-px-100 h-px-100 rounded-4" id="uploadedAvatar"/>
                        </a>            
                        
                    </div>
                </div>
                <div class="col">
                    <h6 class="mb-0" >{{ $mahasiswa->nama ?? '-' }}</h6><hr>
                    <div><i class="ri-mail-check-line"></i> {{ $mahasiswa->email ?? '-' }}</div>
                </div>
                <div class="col">
                    <h6 class="mb-0"> <i class="ri-time-zone-line"></i> Joined : {{ \Carbon\Carbon::parse($profile->created_at)->format('Y-m-d H:i:s') }}</h6><hr>
                    <div> <i class="ri-time-zone-line"></i> Last Login : {{ $profile->last_login }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
        <form method="post" id="form-update" action="{{ url('mhsprofile/update') }}">
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
            <button type="submit" id="btnSubmit_form-update" class="btn btn-sm btn-primary rounded-pill"><i class="ri-save-2-fill pe-1"></i>Save changes</button>
        </form>
        </div>
    </div>
    </div>
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
				  $("#btnSubmit_"+id+"").html("<span class='spinner-border' role='status'></span> loading...");			
			  },
			  complete:function(){
				  $("#btnSubmit_"+id+"").prop("disabled",false);
				  $("#btnSubmit_"+id+"").html(btnHtml);	
			  },
			  success: function(ret) {
				if(ret.success == true){
                    toastr.success(ret.message)		
                }else{
                    toastr.warning(ret.message)
                }
			  },
			  error:function(xhr,ajaxOptions,thrownError){
				  console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);
                  toastr.error(thrownError)				
			  }	

		  });
	});
    $("#form-update").on("submit",function(){       
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