@extends('layouts.app')
@section('title','Data Mahasiswa Mentor')
@section('container')
<x-page-header /> 
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