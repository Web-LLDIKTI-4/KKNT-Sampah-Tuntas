@extends('layouts.app')
@section('title','Data Kehadiran')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-header">
        <a class="btn btn-sm btn-primary modalButton" href="#modalku" data-bs-toggle="modal" data-src="{{ url('logkehadiran/tambahizin') }}" title="Laporan Izin"><i class="ri-calendar-todo-line pe-1"></i> Laporan Izin</a>
        <a class="btn btn-sm btn-primary modalButton" href="#modalku" data-bs-toggle="modal" data-src="{{ url('logkehadiran/tambah') }}" title="Laporan Kehadiran"><i class="ri-calendar-todo-line pe-1"></i> Laporan Kehadiran</a>
    </div>
    <div class="card-body">
        <p id="resultcontent">loding data</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('logkehadiran/listdata') }}");
    })
</script>
@stop 