@extends('layouts.app')
@section('title','Data Kehadiran')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-header">
        <x-btn-modal url="{{ url('logkehadiran/tambahizin') }}" title="Laporan Izin"><i class="ri-calendar-todo-line pe-1"></i> Laporan Izin</x-btn-modal>
        <x-btn-modal url="{{ url('logkehadiran/tambah') }}" title="Laporan Kehadiran"><i class="ri-calendar-todo-line pe-1"></i> Laporan Kehadiran</x-btn-modal>
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