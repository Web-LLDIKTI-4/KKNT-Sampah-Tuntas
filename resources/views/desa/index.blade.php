@extends('layouts.app')
@section('title','Desa / Kelurahan')
@section('container')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/leaflet/leaflet.css') }}" />
<script src="{{ asset('assets/vendor/libs/leaflet/leaflet.js') }}"></script>
<style>
    .desa-minimap { width: 160px; height: 100px; border-radius: 4px; background: #eef1f4; }
    .desa-minimap .leaflet-control-attribution { font-size: 8px; line-height: 1.2; padding: 0 2px; }
</style>
<x-crud-index :list-url="url('desa/listdata')" :add-url="url('desa/tambah')" />
@stop
