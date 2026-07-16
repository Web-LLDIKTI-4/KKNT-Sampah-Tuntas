@extends('layouts.app')
@section('title','Log Bulanan Mahasiswa')
@section('container')

<x-page-header title="Log Bulanan Mahasiswa" subtitle="Data Log Bulanan" /> 

<div class="card">
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $('#modalku').on('show.bs.modal', function () {
            $(".modal-dialog").addClass("modal-lg");
        })
        $("#resultcontent").load("{{ url('admlogbulanan/listdatagroup') }}");
        $("body").on("submit","#form-simpan",function(e){
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
                    $("#btnSubmit_"+id+"").html("<span class='spinner-border' role='status' aria-hidden='true'></span> Loading...");			
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
                        toastr.success(ret.message)				
                    }else{                    
                        toastr.warning(ret.message)	
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