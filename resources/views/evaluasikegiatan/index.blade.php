@extends('layouts.app')
@section('title','Evaluasi Kegiatan')
@section('container')
<x-page-header /> 
<!-- Nav -->
 

<div class="btn-group mb-1 ms-4">
    <a href="{{ url('admevaluasikegiatan/pertanyaanevaluasi') }}" class="btn btn-secondary btn-sm waves-effect waves-light">
        <span class="d-none d-sm-block">Pertanyaan Evaluasi Kegiatan</span><i class="ri-play-fill d-sm-none"></i>
    </a>
</div>
<div class="card">
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('admevaluasikegiatan/hasilevaluasi') }}");
    })
</script>
@stop 