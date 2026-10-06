@extends('layouts.app')
@section('title','Kehadiran Mahasiswa')
@section('container')

<x-page-header title="Kehadiran Mahasiswa" subtitle="Data Kehadiran" /> 

<div class="card">
    <div class="card-header">
        <x-button.export-bulan :url="route('export.logkehadiran')" label="Export Semua Kehadiran" />
    </div>
    <div class="card-body">
        <p id="resultcontent">Loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('admlogkehadiran/listdatagroup') }}");
    })
</script>
@stop 