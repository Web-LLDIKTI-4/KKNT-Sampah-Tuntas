<form id="form-tambah" method="post" action="{{ url('user/insertuserpt') }}">
    @csrf
    @method('PUT')
    <div class="row">   
        <x-form.input name="name" label="Nama" wrapper-class="form-group col form-floating form-floating-outline mb-6">
            <span id="name_error" class="text-danger"></span>
        </x-form.input>
        <x-form.select name="kodept" label="Perguruan Tinggi" input-class="form-control select2" wrapper-class="form-group col form-floating form-floating-outline mb-6" :placeholder="false">
                @foreach($sp as $item)
                    <option value="{{$item->npsn}}">{{$item->nm_lemb}}</option>
                @endforeach
            <x-slot:after>
            <span id="kodept_error" class="text-danger"></span>
            </x-slot:after>
        </x-form.select>
    </div>
    <x-form.select name="location_program" label="Lokasi Program" input-class="form-control form-control-sm select2" :placeholder="false" id="location_program" required>
            <option value="" selected>--pilih--</option>
            @foreach($locationPrograms as $val)
                <option value="{{ $val->id }}">{{$val->nama_lokasi}}</option>
            @endforeach
        <x-slot:after>
        <span id="location_program_error" class="text-danger"></span>
        </x-slot:after>
    </x-form.select>
    <div class="row">   
        <x-form.input name="password" label="Kata Sandi" wrapper-class="form-group col form-floating form-floating-outline mb-6">
            <span id="password_error" class="text-danger"></span>
        </x-form.input>
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