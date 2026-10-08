@extends('layouts.app')
@section('title', 'Pendataan Sampah Penduduk')
@section('container')

<x-page-header title="Pendataan Sampah Penduduk" subtitle="Data Sampah Penduduk Setiap Rumah" />

<div class="card">
    <div class="card-body">
        <p id="resultcontent">Memuat data...</p>
    </div>
</div>
<script>
    $(function () {
        $('#resultcontent').load("{{ route('pendataanpemilahan.listdatagroup') }}");
    });
</script>
@stop
