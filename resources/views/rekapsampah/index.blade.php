@extends('layouts.app')
@section('title', 'Rekap Data Sampah')
@section('container')

<x-page-header
    icon="ri-recycle-line"
    :title="$isPt ? 'Rekap Data Sampah Perguruan Tinggi' : 'Rekap Data Sampah'"
    subtitle="Detail per log Pendataan Sampah Penduduk, dikelompokkan per lokasi program dan bulan beserta subtotalnya." />

<div class="card">
    <div class="card-body">
        <div data-filter-host="{{ route('rekapsampah') }}">
            @include('rekapsampah._tabel')
        </div>
    </div>
</div>

<script src="{{ asset('js/drilldown.js') }}?v={{ filemtime(public_path('js/drilldown.js')) }}"></script>
@stop
