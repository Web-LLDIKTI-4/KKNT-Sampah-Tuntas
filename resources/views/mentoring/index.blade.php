@extends('layouts.app')
@section('title','Mahasiswa')
@section('container')

<x-page-header /> 

<div class="card">
    <div class="card-header">
        <x-button modal="{{ url('dplmentoring/tambah') }}" title="Tambah Mahasiswa" icon="ri-add-fill">Tambah Mahasiswa
        </x-button>
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