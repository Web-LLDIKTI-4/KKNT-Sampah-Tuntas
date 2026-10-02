@extends('layouts.app')
@section('title','Perguruan Tinggi')
@section('container')
<x-page-header subtitle="Daftar {{ $__env->yieldContent('title') }}" />
<div class="card">
    <div class="card-header">
        <div class="d-flex">
            <form id="form-tambah" method="post" action="{{ url('perguruantinggi/getdata') }}">
                @csrf
                @method('PUT')
                <x-button.save formId="form-tambah" icon="ri-loop-left-line">Sync PDDIKTI</x-button.save>
            </form>&nbsp;
            <x-button modal="{{ url('perguruantinggi/tambah') }}" title="Tambah Perguruan Tinggi" icon="ri-play-list-add-line">Tambah Perguruan Tinggi</x-button>
        </div>
    </div>
    <div class="card-body">
        <p id="resultcontent">Loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('perguruantinggi/listdata') }}");

        $("#form-tambah").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var dString = $(this).serialize();
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
                    var table = $('#dataTable').DataTable(); // Menginisialisasi objek tabel
                    // Memuat ulang data tabel secara manual
                    table.ajax.reload();
                    toastr.success(ret.messages)		
                }else{
                    toastr.warning(ret.messages)
                }
            },
            error:function(xhr,ajaxOptions,thrownError){
                console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
            }			
            
        });
        return false;
    });

    $("body").on("submit","#tambahpilih",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var dString = $(this).serialize();
        $("#kodept_error").html('');
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
                    var table = $('#dataTable').DataTable(); // Menginisialisasi objek tabel
                    // Memuat ulang data tabel secara manual
                    table.ajax.reload();
                    toastr.success(ret.messages)			
                }else{
                   
                    toastr.warning(ret.messages)
                    if (ret.hasOwnProperty('errors')) {
                        // Ada kesalahan validasi
                        var errors = ret.errors;

                        // Menghapus pesan error sebelumnya
                        $('.errors-message').remove(); // Menghapus pesan error sebelumnya

                        // Menampilkan pesan error pada setiap field
                        $.each(errors, function(key, value) {
                            var inputField = $('[name="' + key + '"]');
                            var formFloating = inputField.closest('.form-floating');

                            // Jika elemen pesan error belum ada, tambahkan elemen tersebut
                            if (formFloating.find('.errors-message').length === 0) {
                                formFloating.append('<div class="errors-message text-danger">' + value[0] + '</div>');
                            }

                            // Menambahkan event listener untuk menghapus pesan error saat field mendapatkan fokus
                            inputField.on('focus change', function(){
                                formFloating.find('.errors-message').remove();
                            });
                        });
                    }	
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