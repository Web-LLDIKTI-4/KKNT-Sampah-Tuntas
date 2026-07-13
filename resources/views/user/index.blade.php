@extends('layouts.app')
@section('title','Kelola User')
@section('container')
<x-page-header /> 
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