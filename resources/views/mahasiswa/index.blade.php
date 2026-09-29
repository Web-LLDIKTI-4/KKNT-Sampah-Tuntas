@extends('layouts.app')
@section('title','Mahasiswa')
@section('container')
<x-page-header subtitle="Daftar {{ $__env->yieldContent('title') }}" />

<div class="card">
    <div class="card-header">
        <x-btn-modal url="{{ url('mahasiswa/import') }}" title="Import Data">
            <i class="ri-chat-upload-fill me-2"></i> Import Data Mahasiswa
        </x-btn-modal>
    </div>
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('mahasiswa/listdata') }}");
    })
</script>
@stop 