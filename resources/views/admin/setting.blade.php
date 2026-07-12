@extends('layouts/template')
@section('title','Home')
@section('container')
<div class="page-title">
    <div class="row justify-content-between align-items-center">
        <div class="col-md-6 d-flex align-items-center justify-content-between justify-content-md-start mb-3 mb-md-0">
            <!-- Page title + Go Back button -->
            <div class="d-inline-block">
                <h5 class="h4 d-inline-block font-weight-400 mb-0 text-white">Setting Akun</h5>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <form method="post" id="form-update" action="{{ url('setting/update') }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label>Masukan Password Lama</label>
                <input type="text" name="plama" class="form-control">
                <span id="plama_error" class="text-danger"></span>
            </div>
            <div class="row">
                <div class="form-group col">
                    <label>Masukan Password Baru</label>
                    <input type="text" name="pbaru" class="form-control">
                    <span id="pbaru_error" class="text-danger"></span>
                </div>
                <div class="form-group col">
                    <label>Ulangi Password Baru</label>
                    <input type="text" name="pbaruulangi" class="form-control">
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
                    $.notify(ret.message, {
                        type: 'success',
                        animate: {
                            enter: 'animated rollIn',
                            exit: 'animated rollOut'
                        },
                        z_index: 2000
                    });			
                }else{
                    $.each(ret.errors, function(key, value) {
                        $("#" + key + "_error").html(value[0]); // Menampilkan pesan error di dalam field yang sesuai
                    });
                    $.notify(ret.message, {
                        type: 'danger',
                        animate: {
                            enter: 'animated rollIn',
                            exit: 'animated rollOut'
                        },
                        z_index: 2000
                    });
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