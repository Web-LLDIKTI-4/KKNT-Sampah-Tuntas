@extends('layouts.app')
@section('title','Kelola Pengguna')
@section('container')
<x-page-header /> 
<div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3">
        <x-btn-modal url="{{ url('user/getdatamember') }}" class="btn btn-primary btn-sm modalButton" title="Tambah Pengguna Mahasiswa">
            <i class="ri-user-add-fill me-1"></i>
            Tambah Pengguna Mahasiswa
        </x-btn-modal>
        <x-btn-modal url="{{ url('user/adduser') }}" class="btn btn-success btn-sm modalButton" title="Tambah Pengguna DPL">
            <i class="ri-user-add-fill me-1"></i>
            Tambah Pengguna DPL
        </x-btn-modal>
        <x-btn-modal url="{{ url('user/adduserpt') }}" class="btn btn-warning btn-sm modalButton" title="Tambah Pengguna Perguruan Tinggi">
            <i class="ri-user-add-fill me-1"></i>
            Tambah Pengguna Perguruan Tinggi
        </x-btn-modal>
        <x-btn-modal url="{{ url('user/adduserkepala') }}" class="btn btn-info btn-sm modalButton" title="Tambah Pengguna Kepala">
            <i class="ri-user-add-fill me-1"></i>
            Tambah Pengguna Kepala
        </x-btn-modal>
    </div>
    <div class="card-body">
        <p id="resultcontent">loading user...</p>
    </div>
</div>
<script>
$(function(){
    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-xl');
    })
    $("#resultcontent").load("{{ url('user/listdata') }}");

})    
</script>
@stop 