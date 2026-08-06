@extends('layouts.app')
@section('title','Log Harian')
@section('container')

<x-page-header /> 

<div class="card">
    {{-- <div class="card-header">
        <x-btn-modal url="{{ url('logkegiatan/tambah') }}" title="Tambah Data">
            <i class="ri-add-fill me-2"></i>
            Tambah Data
        </x-btn-modal>
    </div> --}}
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $('#modalku').on('show.bs.modal', function (e) {
            $(".modal-dialog").addClass('modal-lg');
        })
        $("#resultcontent").load("{{ url('logkegiatan/listdata') }}");
    })
</script>
@stop 