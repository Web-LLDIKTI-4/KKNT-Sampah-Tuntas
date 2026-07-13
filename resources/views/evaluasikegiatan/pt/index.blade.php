@extends('layouts/user')
@section('title','Evaluasi kegiatan')
@section('container')
<div class="page-title">
    <div class="row justify-content-between align-items-center">
        <div class="col-md-6 d-flex align-items-center justify-content-between justify-content-md-start mb-3 mb-md-0">
            <!-- Page title + Go Back button -->
            <div class="d-inline-block">
                <h5 class="h4 d-inline-block font-weight-400 mb-0 text-white">Evaluasi kegiatan</h5>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <p id="resultcontent">loding data</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('ptevaluasikegiatan/tambah') }}");
        $("body").on("submit","#form-tambah",function(e){
            e.preventDefault();     
            var action = $(this).attr("action");
            var id = $(this).attr("id");
            var btnHtml = $("#btnSubmit_"+id+"").html();
            var dString = $(this).serialize();
            $.ajax({
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
                        var table = $('#dataTable').DataTable(); // Menginisialisasi objek tabel
                        // Memuat ulang data tabel secara manual
                        table.ajax.reload();
                        $.notify(ret.message, {
                            type: 'success',
                            animate: {
                                enter: 'animated rollIn',
                                exit: 'animated rollOut'
                            },
                            z_index: 2000
                        });			
                    }else{                    
                        $.notify(ret.message, {
                            type: 'danger',
                            animate: {
                                enter: 'animated rollIn',
                                exit: 'animated rollOut'
                            },
                            z_index: 2000
                        });
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
                    }
                },
                error:function(xhr,ajaxOptions,thrownError){
                    console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
                }			
                
            })
        })
    })
</script>
@stop 