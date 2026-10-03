@extends('layouts.app')
@section('title','Log Kegiatan Bulanan')
@section('container')

<x-page-header /> 

<div class="card mb-6">
    <div class="card-body">
        <p id="listdata">list data...</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="post" id="form-bulan" action="{{ url('dpllaporan/tambah') }}" data-ajax-form data-result-target="#resultcontent">
            @csrf
            <div class="row">
                <x-form.select name="tahun" label="Pilih Tahun" input-class="form-control form-control-sm" wrapper-class="col form-group form-floating form-floating-outline mb-6" :placeholder="false">
                        @for($th=date('Y')-1; $th<=date('Y'); $th++)
                            <option value="{{ $th }}" @if($th == date("Y")) selected @endif>{{ $th }}</option>
                        @endfor
                </x-form.select>
                <x-form.select name="bulan" label="Pilih Bulan" input-class="form-control form-control-sm" wrapper-class="col form-group form-floating form-floating-outline mb-6" :placeholder="false">
                        @foreach ($namaBulan as $nomorBulan => $bulan)
                            <option value="{{ $nomorBulan }}" @if($nomorBulan == date("m")) selected @endif>{{ $bulan }}</option>
                        @endforeach
                </x-form.select>
                <div class="col mt-1">
                    <x-button.save formId="form-bulan" size="lg" icon="ri-filter-3-fill">
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
    $("#listdata").load("{{ url('dpllaporan/listdata') }}");
   
    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-lg');
    })
    
})
</script>
@stop 