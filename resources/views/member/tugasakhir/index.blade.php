@extends('layouts/template')
@section('title','Data Tugas Akhir')
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
    <div class="card-header">
        <a class="btn btn-sm btn-primary modalButton" href="#modalku" data-bs-toggle="modal" data-src="{{ url('tugasakhir/tambah') }}" title="Tambah Data">Tambah Data</a>
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