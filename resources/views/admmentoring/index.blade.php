@extends('layouts.app')
@section('title','Bimbingan Mahasiswa')
@section('container')

<x-page-header icon="ri-user-shared-fill" subtitle="Penugasan mahasiswa ke Dosen Pembimbing Lapangan" />

<div class="card">
    <div class="card-header">
        <x-button modal="{{ route('admmentoring.tambah') }}" title="Tugaskan Mahasiswa ke DPL" icon="ri-user-add-fill">Tugaskan Mahasiswa
        </x-button>
    </div>
    <div class="card-body">
        <p id="resultcontent">List data...</p>
    </div>
</div>
<script>
$(function(){
    // Namespaced so the handler is registered once per page
    $('#modalku').off('show.bs.modal.admmentoring').on('show.bs.modal.admmentoring', function () {
        $(this).find('.modal-dialog').addClass('modal-xl');
    });
    loadPartial('#resultcontent', "{{ route('admmentoring.listdata') }}");
})
</script>
@stop
