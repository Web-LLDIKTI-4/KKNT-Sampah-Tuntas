@extends('layouts/template')
@section('title','Kelola User')
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
    <a href="#modalku" data-bs-toggle="modal" class="btn btn-primary btn-sm modalButton" data-src="{{ url('user/getdatamember') }}" title="Buat User Mahasiswa">tambah user mahasiswa</a>
    <a href="#modalku" data-bs-toggle="modal" class="btn btn-primary btn-sm modalButton" data-src="{{ url('user/adduser') }}" title="Buat User">tambah user non mahasiswa</a>
    <a href="#modalku" data-bs-toggle="modal" class="btn btn-primary btn-sm modalButton" data-src="{{ url('user/adduserpt') }}" title="Buat User">tambah user perguruan tinggi</a>
    </div>
    <div class="card-body">
        <p id="resultcontent">loding user...</p>
    </div>
</div>
<script>
$(function(){
    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-xl');
    })
    $("#resultcontent").load("{{ url('user/listdata') }}");

})    
</script>
@stop 