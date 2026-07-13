@extends('layouts.app')
@section('title','Data Mahasiswa Mentor')
@section('container')
<x-page-header /> 
<div class="card">
    <div class="card-header">
        <x-btn-modal url="{{ url('dplmentoring/tambah') }}" class="btn btn-primary btn-sm modalButton" title="Tambah Mahasiswa">Tambah mahasiswa</x-btn-modal>
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