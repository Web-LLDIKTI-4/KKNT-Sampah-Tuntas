@extends('layouts.app')
@section('title','Log Bulanan Mahasiswa')
@section('container')

<div class="d-flex mb-4 gap-4">
    <div class="avatar avatar-md">
        <div class="avatar-initial bg-label-primary rounded-4">
            <i class="ri-information-2-fill ri-30px"></i>
        </div>
    </div>
    <div>
        <h5 class="mb-0">
            <span class="align-middle">Log Bulanan Mahasiswa</span>
        </h5>
        <span>Data Log Bulanan</span>
    </div>
</div>

<div class="card mb-5">
    <div class="card-body">
        <p id="listdata">list data...</p>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <form method="post" id="form-bulan" action="{{ url('logbulanan/tambah') }}">
            @csrf
            <div class="row">
                <div class="col form-group form-floating form-floating-outline mb-6">
                    <select name="tahun" class="form-control form-control-sm">
                        @for($th=date('Y')-1; $th<=date('Y'); $th++)
                        <option value="{{ $th }}" @if($th == date("Y")) selected @endif>{{ $th }}</option>
                        @endfor
                    </select>
                    <label>Pilih Tahun</label>
                </div>
                <div class="col form-group form-floating form-floating-outline mb-6">
                    <select name="bulan" class="form-control form-control-sm">
                        @foreach ($namaBulan as $nomorBulan => $bulan)
                            <option value="{{ $nomorBulan }}" @if($nomorBulan == date("m")) selected @endif>{{ Carbon\Carbon::create()->month($nomorBulan)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                    <label> Pilih Bulan </label>
                </div>
                <div class="col mt-1">
                    <x-btn-save formId="form-bulan" class="btn btn-primary btn-lg">
                        Input Log Bulanan
                    </x-btn-save>
                </div>
            </div>
        </p> 
    </div>
    <div class="card-body">
        <p id="resultcontent"></p>
    </div>
</div>
<script>
$(function(){
    $("#listdata").load("{{ url('logbulanan/listdata') }}");

    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-lg');
    })
    $("body").on("submit","#form-bulan,[id^=form-bulan-]",function(){       
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
                $("#resultcontent").html(ret);
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