@extends('layouts.app')
@section('title','Kelola User')
@section('container')
<x-page-header /> 
<div class="card">
    <div class="card-header">
    <x-btn-modal url="{{ url('user/getdatamember') }}" class="btn btn-primary btn-sm modalButton" title="Buat User Mahasiswa">tambah user mahasiswa</x-btn-modal>
    <x-btn-modal url="{{ url('user/adduser') }}" class="btn btn-primary btn-sm modalButton" title="Buat User">tambah user non mahasiswa</x-btn-modal>
    <x-btn-modal url="{{ url('user/adduserpt') }}" class="btn btn-primary btn-sm modalButton" title="Buat User">tambah user perguruan tinggi</x-btn-modal>
    </div>
    <div class="card-body">
        <p id="resultcontent">loding user...</p>
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