@extends('layouts.app')
@section('title','Laporan Akhir')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-header">
        <x-button modal="{{ url('tugasakhir/tambah') }}" title="Tambah Data" icon="ri-add-fill">Tambah Data
        </x-button>
    </div>
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $('#modalku').on('show.bs.modal', function (e) {
            $(".modal-dialog").addClass('modal-lg');
        })
        $("#resultcontent").load("{{ url('tugasakhir/listdata') }}");
    })
</script>
@stop 