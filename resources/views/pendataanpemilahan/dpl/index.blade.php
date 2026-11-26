@extends('layouts.app')
@section('title', 'Pendataan Sampah Penduduk')
@section('container')

<x-page-header title="Pendataan Sampah Penduduk" subtitle="Data Sampah Penduduk Setiap Rumah" />

<div class="card">
    @if (in_array(auth()->user()->role, ['dpl', 'pt'], true))
        <div class="card-header">
            <x-button.export-bulan :url="route('pendataanpemilahan.export.semua')" label="Export Semua Pendataan Sampah" />
        </div>
    @endif
    <div class="card-body">
        <p id="resultcontent">Memuat data...</p>
    </div>
</div>
<script>
    $(function () {
        loadPartial('#resultcontent', "{{ route('pendataanpemilahan.listdatagroup') }}");
    });
</script>
@stop
