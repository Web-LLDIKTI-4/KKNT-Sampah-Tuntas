<form id="form-update" method="post" action="{{ url('user/updateuser') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id" value="{{ $data->id }}">

    <div class="row">   
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="name" class="form-control form-control-sm" value="{{ $data->name }}" required>
            <label>Nama</label>
            <span id="name_error" class="text-danger"></span>
        </div>
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="email" class="form-control form-control-sm" value="{{ $data->email }}" required readonly>
            <label>Email</label>
            <span id="email_error" class="text-danger"></span>
        </div>
    </div>
    @if (in_array($data->role, ['dpl', 'mahasiswa']))
        <div class="form-group form-floating form-floating-outline mb-6">
            <select  id="location_program" class="form-control form-control-sm select2" name="location_program" required>
                <option value="" selected>--pilih--</option>
                @foreach($locationPrograms as $val)
                    <option value="{{ $val->id }}" @if($data->location_program == $val->id) selected @endif>{{$val->nama_lokasi}}</option>
                @endforeach
            </select>
            <label>Lokasi Program</label>
            <span id="location_program_error" class="text-danger"></span>
        </div>
    @endif
    <div class="row">   
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="password" class="form-control form-control-sm">
            <label>Kata Sandi</label>
            <span id="password_error" class="text-danger"></span>
        </div>
    </div>

    @if ($data->role == 'mahasiswa')
        <div class="form-group form-floating form-floating-outline mb-6">
            <select  id="akses" class="form-control form-control-sm select2" name="akses">
                <option value="null" selected>--pilih--</option>
                @foreach($akses as $key=>$val)
                    <option value="{{ trim($key) }}" @if($data->akses == $key) selected @endif>{{$val}}</option>
                @endforeach
            </select>  
            <label>Tambah akses (<span class="text-danger">Hanya role mahasiswa yang bisa jadi Ketua Kelompok</span>)</label>
            <span id="akses_error" class="text-danger"></span>
        </div>
    @endif
    <hr>
    <x-btn-save formId="form-update">Simpan</x-btn-save>
</form>
        
<script>
    $(function(){
        $('.select2').select2({
            dropdownParent: $('#modalku')
        });

        $("#form-update").on("submit",function(){       
            var action = $(this).attr("action");
            var id = $(this).attr("id");
            var btnHtml = $("#btnSubmit_"+id+"").html();
            var dString = $(this).serialize();
            $("#name_error,#email_error,#password_error,#role_error").html('');
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
                        $("#resultcontent").load("{{ url('user/listdata') }}");
                        toastr.success(ret.message)		
                    }else{
                        $.each(ret.errors, function(key, value) {
                            $("#" + key + "_error").html(value[0]); // Menampilkan pesan error di dalam field yang sesuai
                        });
                        toastr.warning(ret.message)	
                    }
                },
                error:function(xhr,ajaxOptions,thrownError){
                    console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
                }			
                
            });
            return false;
        });
    });
</script>