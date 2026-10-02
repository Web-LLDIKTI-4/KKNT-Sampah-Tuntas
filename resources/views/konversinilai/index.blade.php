@extends('layouts.app')
@section('title','Konversi Nilai')
@section('container')

<x-page-header /> 

<div class="card">
    @if (in_array(Auth::user()->role, ['dpl']))
        <div class="card-header">
            <x-button modal="{{ url('dplkonversinilai/tambah') }}" title="Tambah Data" icon="ri-add-fill">Tambah Data
            </x-button>
        </div>
    @endif
    <div class="card-body">
        <p id="resultcontent">Loading data...</p>
    </div>
</div>
<script>
$(function(){
    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-lg');
    })
    $("#resultcontent").load("{{ url('dplkonversinilai/listdata') }}");})
</script>
@stop 