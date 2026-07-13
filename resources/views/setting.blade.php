@extends('layouts.app')
@section('title','Setting Akun')
@section('container')
<x-page-header icon="ri-lock-line" title="Setting Akun" subtitle="Kelola Password Akun" />
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
            <x-btn-save formId="form-update">Simpan</x-btn-save>
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
