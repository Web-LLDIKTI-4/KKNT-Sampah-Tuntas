@extends('layouts.app')
@section('title','Data Nilai Free Form')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-header">
        <x-button modal="{{ url('dplfreeform/tambah') }}" title="Tambah Data">Tambah Data</x-button>
    </div>
    <div class="card-body">
        <p id="resultcontent">Loading data...</p>
    </div>
</div>
<script>
$(function(){
    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-lg');
    })
    $("#resultcontent").load("{{ url('dplfreeform/listdata') }}");})
</script>
@stop 