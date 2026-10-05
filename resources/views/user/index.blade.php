@extends('layouts.app')
@section('title','Kelola Pengguna')
@section('container')
<x-page-header /> 
<div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3">
        <x-button modal="{{ url('user/getdatamember') }}" title="Tambah Pengguna Mahasiswa" icon="ri-user-add-fill">Tambah Pengguna Mahasiswa
        </x-button>
        <x-button modal="{{ url('user/adduser') }}" variant="success" title="Tambah Pengguna DPL" icon="ri-user-add-fill">Tambah Pengguna DPL
        </x-button>
        <x-button modal="{{ url('user/adduserpt') }}" variant="warning" title="Tambah Pengguna Perguruan Tinggi" icon="ri-user-add-fill">Tambah Pengguna Perguruan Tinggi
        </x-button>
        <x-button modal="{{ url('user/adduserkepala') }}" variant="info" title="Tambah Pengguna Kepala/Pemda" icon="ri-user-add-fill">Tambah Pengguna Kepala/Pemda
        </x-button>
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