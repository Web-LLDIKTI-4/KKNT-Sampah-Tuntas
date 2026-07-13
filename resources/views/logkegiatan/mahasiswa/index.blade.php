@extends('layouts.app')
@section('title','Data Log Kegiatan')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-header">
        <x-btn-modal url="{{ url('logkegiatan/tambah') }}" title="Tambah Data">Tambah Data</x-btn-modal>
    </div>
    <div class="card-body">
        <p id="resultcontent">loding data</p>
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