@extends('layouts.app')
@section('title','Konversi Nilai')
@section('container')

<x-page-header /> 

<div class="card">
    @if (in_array(Auth::user()->role, ['dpl']))
        <div class="card-header">
            <x-btn-modal url="{{ url('dplkonversinilai/tambah') }}" class="btn btn-primary btn-sm modalButton" title="Tambah Data">
                <i class="ri-add-fill me-2"></i>
                Tambah Data
            </x-btn-modal>
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