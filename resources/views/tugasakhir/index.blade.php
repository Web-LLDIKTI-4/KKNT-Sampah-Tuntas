@extends('layouts.app')
@section('title','Data Tugas Akhir')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-header">
        <x-btn-modal url="{{ url('tugasakhir/tambah') }}" title="Tambah Data">Tambah Data</x-btn-modal>
    </div>
    <div class="card-body">
        <p id="resultcontent">loding data</p>
    </div>
</div>
<script>
    $(function(){
        $('#modalku').on('show.bs.modal', function (e) {
            $(".modal-dialog").addClass('modal-lg');
        })
        $("#resultcontent").load("{{ url('tugasakhir/listdata') }}");
        $("body").on("submit","#form-tambah,#form-ubah",function(){       
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
                        $("#resultcontent").load("{{ url('tugasakhir/listdata') }}");
                        $("#modalku").modal("hide");
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
@stop 