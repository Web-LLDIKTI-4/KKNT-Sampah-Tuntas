@extends('layouts/template')
@section('title','Data Mahasiswa Mentor')
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
        <a href="#modalku" data-bs-toggle="modal" class="btn btn-primary btn-sm modalButton" data-src="{{ url('dplmentoring/tambah') }}" title="Tambah Mahasiswa">Tambah mahasiswa</a>
    </div>
    <div class="card-body">
        <p id="resultcontent">List data...</p>
    </div>
</div>
<script>
$(function(){
    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-xl');
    })
    $("#resultcontent").load("{{ url('dplmentoring/listdata') }}");

})
</script>
@stop 