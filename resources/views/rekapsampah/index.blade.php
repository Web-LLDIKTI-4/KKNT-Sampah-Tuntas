@extends('layouts.app')
@section('title', 'Rekap Data Sampah')
@section('container')

<x-page-header
    icon="ri-recycle-line"
    :title="$isPt ? 'Rekap Data Sampah Perguruan Tinggi' : 'Rekap Data Sampah'"
    subtitle="Data sampah bulanan per kelurahan yang diisi ketua kelompok, dijumlahkan per kecamatan setiap bulan." />

<div class="card">
    <div class="card-body">
        <div data-filter-host="{{ route('rekapsampah') }}">
            @include('rekapsampah._tabel')
        </div>
    </div>
</div>

<script src="{{ asset('js/drilldown.js') }}?v={{ filemtime(public_path('js/drilldown.js')) }}"></script>
@stop
