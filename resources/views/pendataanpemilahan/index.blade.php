@extends('layouts.app')
@section('title', 'Pendataan Sampah Penduduk')
@section('container')

<x-page-header title="Pendataan Sampah Penduduk" />

<div class="card">
    <div class="card-header">
        <div class="d-flex flex-wrap justify-content-end align-items-center gap-2">
            {{-- Data baru diinput lewat Log Harian
            <x-button id="btnTambahPendataan" variant="dark" :modal="url('pendataanpemilahan/tambah')" icon="ri-add-line" title="Tambah Pendataan">
                Tambah Pendataan
            </x-button>
            --}}
            <x-button.export-bulan :url="route('pendataanpemilahan.export')" label="Export Pendataan Sampah" />
        </div>
    </div>
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function () {
        $('#modalku').on('show.bs.modal', function () {
            $('.modal-dialog').addClass('modal-lg');
        });
        loadPartial('#resultcontent', "{{ url('pendataanpemilahan/listdata') }}");
    });
</script>
@stop
