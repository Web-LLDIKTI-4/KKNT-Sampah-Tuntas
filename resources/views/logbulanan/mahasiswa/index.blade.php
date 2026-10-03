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
        <form method="post" id="form-bulan" action="{{ url('logbulanan/tambah') }}" data-ajax-form data-result-target="#resultcontent">
            @csrf
            <div class="row">
                <x-form.select name="tahun" label="Pilih Tahun" input-class="form-control form-control-sm" wrapper-class="col form-group form-floating form-floating-outline mb-6" :placeholder="false">
                        @for($th=date('Y')-1; $th<=date('Y'); $th++)
                        <option value="{{ $th }}" @if($th == date("Y")) selected @endif>{{ $th }}</option>
                        @endfor
                </x-form.select>
                <x-form.select name="bulan" label="Pilih Bulan" input-class="form-control form-control-sm" wrapper-class="col form-group form-floating form-floating-outline mb-6" :placeholder="false">
                        @foreach ($namaBulan as $nomorBulan => $bulan)
                            <option value="{{ $nomorBulan }}" @if($nomorBulan == date("m")) selected @endif>{{ Carbon\Carbon::create()->month($nomorBulan)->translatedFormat('F') }}</option>
                        @endforeach
                </x-form.select>
                <div class="col mt-1">
                    <x-button.save formId="form-bulan" size="lg">
                        Isi Log Bulanan
                    </x-button.save>
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
})
</script>
@stop 