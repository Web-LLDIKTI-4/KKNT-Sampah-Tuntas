@extends('layouts.app')
@section('title','Evaluasi Kegiatan')
@section('container')

<x-page-header /> 

<!-- Nav -->
<div class="card">
    <div class="card-header d-flex flex-column flex-md-row align-items-center gap-3">
        <div class="btn-group justify-center" role="group" aria-label="Basic example">
            <a href="{{ url('admevaluasikegiatan') }}" class="btn btn-info btn-sm waves-effect waves-light">
                <i class="ri-pass-valid-line d-none d-md-block me-2"></i>
                <span>Data Hasil Evaluasi</span>
            </a>
            <a href="{{ url('admevaluasikegiatan/pertanyaanevaluasi') }}" class="btn btn-secondary btn-sm waves-effect waves-light">
                <i class="ri-questionnaire-line d-none d-md-block me-2"></i>
                <span>Data Pertanyaan</span>
            </a>
        </div>
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
        // $("#resultcontent").load("{{ url('admevaluasikegiatan/pertanyaanevaluasilistdata') }}");
        $("#resultcontent").load("{{ url('admevaluasikegiatan/hasilevaluasi') }}");
    })
</script>
@stop 