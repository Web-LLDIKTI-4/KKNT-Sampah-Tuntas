<form id="form-update" method="post" action="{{ url('user/updateuser') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id" value="{{ $data->id }}">
    <div class="row">   
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="name" class="form-control form-control-sm" value="{{ $data->name }}" >
            <label>Nama</label>
            <span id="name_error" class="text-danger"></span>
        </div>
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="email" class="form-control form-control-sm" value="{{ $data->email }}">
            <label>Email</label>
            <span id="email_error" class="text-danger"></span>
        </div>
    </div>
    <div class="row">   
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="password" class="form-control form-control-sm">
            <label>Password</label>
            <span id="password_error" class="text-danger"></span>
        </div>
        <div class="form-group col form-floating form-floating-outline mb-6">
            <select id="role" class="form-control form-control-sm" disabled>
                @foreach($role as $row)
                    <option value="{{ trim($row) }}" @if($data->role == $row) selected @endif>{{$row}}</option>
                @endforeach
            </select>
            <label>Role</label>
            <input type="hidden" name="role" value="{{$data->role}}"/>           
            <span id="role_error" class="text-danger"></span>
        </div>
    </div>

    <div class="form-group form-floating form-floating-outline mb-6">
        <select  id="akses" class="form-control form-control-sm" name="akses">
            <option value="null" selected>--pilih--</option>
            @foreach($akses as $key=>$val)
                <option value="{{ trim($key) }}" @if($data->akses == $key) selected @endif>{{$val}}</option>
            @endforeach
        </select>  
        <label>Tambah akses (<span class="text-danger">Hanya role mahasiswa yang bisa jadi PJ Desa</span>)</label>
        <span id="akses_error" class="text-danger"></span>
    </div>
    <hr>
    <x-btn-save formId="form-update">Update Data</x-btn-save>
</form>
        
<script>
    $(function(){
  
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