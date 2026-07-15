@extends('layouts.app')
@section('title','Evaluasi Kegiatan')
@section('container')

<x-page-header /> 

<!-- Nav -->
<div class="card">
    <div class="card-header">
        <a href="{{ url('admevaluasikegiatan/pertanyaanevaluasi') }}" class="btn btn-info btn-sm waves-effect waves-light">
            <span class="d-none d-sm-block">Data Pertanyaan</span>
            <i class="ri-play-fill d-sm-none"></i>
        </a>
        <x-btn-modal url="{{ url('admevaluasikegiatan/tambah') }}" title="Tambah Pertanyaan">
            <i class="ri-add-line me-1"></i>
            Tambah Data Pertanyaan
        </x-btn-modal>
    </div>
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('admevaluasikegiatan/pertanyaanevaluasilistdata') }}");
        // $("#resultcontent").load("{{ url('admevaluasikegiatan/hasilevaluasi') }}");
    })
</script>
@stop 