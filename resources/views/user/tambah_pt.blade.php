<form id="form-tambah" method="post" action="{{ url('user/insertuserpt') }}">
    @csrf
    @method('PUT')
    <div class="row">   
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="name" class="form-control form-control-sm">
            <label>Nama</label>
            <span id="name_error" class="text-danger"></span>
        </div>
        <div class="form-group col form-floating form-floating-outline mb-6">
            <select name="kodept" class="form-control select2">
                @foreach($sp as $item)
                    <option value="{{$item->npsn}}">{{$item->nm_lemb}}</option>
                @endforeach
            </select>
            <label>Perguruan Tinggi</label>
            <span id="kodept_error" class="text-danger"></span>
        </div>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select  id="location_program" class="form-control form-control-sm select2" name="location_program" required>
            <option value="" selected>--pilih--</option>
            @foreach($locationPrograms as $val)
                <option value="{{ $val->id }}">{{$val->nama_lokasi}}</option>
            @endforeach
        </select>
        <label>Lokasi Program</label>
        <span id="location_program_error" class="text-danger"></span>
    </div>
    <div class="row">   
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="password" class="form-control form-control-sm">
            <label>Kata Sandi</label>
            <span id="password_error" class="text-danger"></span>
        </div>
    </div>
    
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
        
<script>
$(function(){
    $('.select2').select2({
       dropdownParent: $('#modalku')
    });

    $("#form-tambah").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var dString = $(this).serialize();
        $("#name_error,#kodept_error,#password_error,#role_error").html('');
        $.ajax({
            dataType:'json',
            type:'post',
            url:action,
            data:dString,
            beforeSend:function(){
                btnLoading($("#btnSubmit_" + id), true);			
            },
            complete:function(){
                btnLoading($("#btnSubmit_" + id), false);	
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