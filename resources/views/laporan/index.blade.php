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
                            <option value="{{ $nomorBulan }}" @if($nomorBulan == date("m")) selected @endif>{{ $bulan }}</option>
                        @endforeach
                    </select>
                    <label> Pilih Bulan </label>
                </div>
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