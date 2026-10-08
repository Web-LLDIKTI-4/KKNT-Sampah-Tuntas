@extends('layouts.app')
@section('title', 'Pendataan Pemilahan Sampah Penduduk')
@section('container')

<x-page-header title="Pendataan Pemilahan Sampah Penduduk" subtitle="Data mahasiswa sesuai cakupan akses Anda." />

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
