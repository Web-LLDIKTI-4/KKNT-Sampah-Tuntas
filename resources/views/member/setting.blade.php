@extends('layouts/template')
@section('title','Setting Akun')
@section('container')
<div class="d-flex mb-4 gap-4">
    <div class="avatar avatar-md">
        <div class="avatar-initial bg-label-primary rounded-4">
        <i class="ri-information-2-fill ri-30px"></i>
        </div>
    </div>
    <div>
        <h5 class="mb-0">
        <span class="align-middle">@yield('title')</span>
        </h5>
        <span>Data @yield('title')</span>
    </div>
</div> 
<div class="card">
    <div class="card-body">
        <form method="post" id="form-update" action="{{ url('setting/update') }}">
            @csrf
            @method('PUT')
            <div class="form-group form-floating form-floating-outline mb-6">
                <input type="text" name="plama" class="form-control">
                <label>Masukan Password Lama</label>
                <span id="plama_error" class="text-danger"></span>
            </div>
            <div class="row">
                <div class="form-group col form-floating form-floating-outline mb-6">
                    <input type="text" name="pbaru" class="form-control">
                    <label>Masukan Password Baru</label>
                    <span id="pbaru_error" class="text-danger"></span>
                </div>
                <div class="form-group col form-floating form-floating-outline mb-6">
                    <input type="text" name="pbaruulangi" class="form-control">
                    <label>Ulangi Password Baru</label>
                    <span id="pbaruulangi_error" class="text-danger"></span>
                </div>
            </div>
            <hr>
            <button type="submit" class="btn btn-sm btn-primary" id="btnSubmit_form-update">Simpan</button>
    </form>
    </div>
</div>
<script>
$(function(){
    $("#form-update").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();
        var dString = $(this).serialize();
        $("#plama_error,#pbaru_error,#pbaruulangi_error").html('');
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
@stop 