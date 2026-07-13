<form id="form-tambah" method="post" action="{{ url('user/updateuserpt') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id" value="{{$user->id}}">
    <div class="row">   
        <div class="form-group col form-floating form-floating-outline mb-6">
            <label>Nama</label>
            <input type="text" name="name" value="{{$user->name}}" class="form-control form-control-sm">
            <span id="name_error" class="text-danger"></span>
        </div>
        <div class="form-group col form-floating form-floating-outline mb-6">
            <label>Perguruan Tinggi</label>
            <select name="kodept" id="select2" class="form-control form-control-sm" readonly>
                @foreach($sp as $item)
                    <option value="{{$item->npsn}}">{{$item->nm_lemb}}</option>
                @endforeach
            </select>
            <span id="kodept_error" class="text-danger"></span>
        </div>
    </div>
    <div class="row">   
        <div class="form-group col form-floating form-floating-outline mb-6">
            <label>Password</label>
            <input type="text" name="password" class="form-control form-control-sm">
            <span id="password_error" class="text-danger"></span>
        </div>
        <div class="form-group col form-floating form-floating-outline mb-6">
            <label>Role</label>
            <select name="role" class="form-control form-control-sm">
                @if($role)
                    @foreach($role as $val)
                        <option value="{{$val}}">{{$val}}</option>
                    @endforeach
                @endif
            </select>
            <span id="role_error" class="text-danger"></span>
        </div>
    </div>
    
    <button type="submit" id="btnSubmit_form-tambah" class="btn btn-sm btn-primary">Simpan</button>
</form>
        
<script>
$(function(){
    $('#select2').select2({
       // theme: "",
    });
    $("#form-tambah").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();
        var dString = $(this).serialize();
        $("#name_error,#kodept_error,#password_error,#role_error").html('');
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