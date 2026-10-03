<form id="form-update" method="post" action="{{ url('user/updateuser') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id" value="{{ $data->id }}">

    <div class="row">   
        <x-form.input name="name" label="Nama" :value="$data->name" wrapper-class="form-group col form-floating form-floating-outline mb-6" required>
            <span id="name_error" class="text-danger"></span>
        </x-form.input>
        <x-form.input name="email" label="Email" :value="$data->email" wrapper-class="form-group col form-floating form-floating-outline mb-6" required readonly>
            <span id="email_error" class="text-danger"></span>
        </x-form.input>
    </div>
    @if (in_array($data->role, ['dpl', 'mahasiswa']))
        <x-form.select name="location_program" label="Lokasi Program" input-class="form-control form-control-sm select2" :placeholder="false" id="location_program" required>
                <option value="" selected>--pilih--</option>
                @foreach($locationPrograms as $val)
                    <option value="{{ $val->id }}" @if($data->location_program == $val->id) selected @endif>{{$val->nama_lokasi}}</option>
                @endforeach
            <x-slot:after>
            <span id="location_program_error" class="text-danger"></span>
            </x-slot:after>
        </x-form.select>
    @endif
    <div class="row">   
        <x-form.input name="password" label="Kata Sandi" wrapper-class="form-group col form-floating form-floating-outline mb-6">
            <span id="password_error" class="text-danger"></span>
        </x-form.input>
    </div>

    @if ($data->role == 'mahasiswa')
        <x-form.select name="akses" input-class="form-control form-control-sm select2" :placeholder="false" id="akses">
            <x-slot:label>Tambah akses (opsional) (<span class="text-danger">Hanya role mahasiswa yang bisa jadi Ketua Kelompok</span>)</x-slot:label>
                <option value="">-- Tidak diubah --</option>
                @foreach($akses as $key=>$val)
                    <option value="{{ trim($key) }}" @if($data->akses == $key) selected @endif>{{$val}}</option>
                @endforeach
            <x-slot:after>
            <span id="akses_error" class="text-danger"></span>
            </x-slot:after>
        </x-form.select>
    @endif
    <hr>
    <x-button.save formId="form-update">Simpan</x-button.save>
</form>
        
<script>
    $(function(){
        $('.select2').select2({
            dropdownParent: $('#modalku')
        });

        $("#form-update").on("submit",function(){       
            var action = $(this).attr("action");
            var id = $(this).attr("id");
            var dString = $(this).serialize();
            $("#name_error,#email_error,#password_error,#role_error").html('');
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