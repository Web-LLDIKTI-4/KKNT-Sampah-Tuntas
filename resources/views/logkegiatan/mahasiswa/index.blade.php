@extends('layouts.app')
@section('title','Log Harian')
@section('container')

<x-page-header /> 

<div class="card">
    <div class="card-header">
        <x-button id="btnTambahLog" class="btn-sm" style="background-color: black; color: white;" modal="modalku" :modalSrc="url('logkegiatan/tambah')" title="Tambah Log Harian" :disabled="$kehadiran && in_array($kehadiran->status_kehadiran, ['izin', 'sakit', 'cuti', 'libur nasional'])">
            <i class="ri-add-line me-2"></i>
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