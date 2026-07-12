@extends('layouts/template')
@section('title','Data Kehadiran')
@section('container')
<div class="d-flex mb-4 gap-4">
    <div class="avatar avatar-md">
        <div class="avatar-initial bg-label-primary rounded-4">
        <i class="ri-information-2-fill ri-30px"></i>
        </div>
    </div>
    <div>
        <h5 class="mb-0">
        <span class="align-middle">@yield('title')</span>
        </h5>
        <span>Data @yield('title')</span>
    </div>
</div> 

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