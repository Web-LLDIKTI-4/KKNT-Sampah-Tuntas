@extends('layouts.app')
@section('title','Log Harian')
@section('container')

<x-page-header /> 

<div class="card">
    <div class="card-header">
        <x-button id="btnTambahLog" variant="dark" style="background-color: black; color: white;" :modal="url('logkegiatan/tambah')" icon="ri-add-line" title="Tambah Log Harian" :disabled="$kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional'])">
            Tambah Log Harian
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
        $("#resultcontent").load("{{ url('logkegiatan/listdata') }}");
    })
</script>
@stop 