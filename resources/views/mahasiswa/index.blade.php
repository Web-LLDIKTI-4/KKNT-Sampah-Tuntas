@extends('layouts.app')
@section('title','Data Mahasiswa')
@section('container')
<x-page-header subtitle="Daftar {{ $__env->yieldContent('title') }}" />

<div class="card">
    <div class="card-header">
        <x-btn-modal url="{{ url('mahasiswa/import') }}" title="Import Data"><i class="ri-chat-upload-fill me-1"></i> Import Data</x-btn-modal>
    </div>
    <div class="card-body">

        <!-- Tampilkan pesan sukses jika ada -->
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <!-- Tampilkan pesan kesalahan jika ada -->
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('mahasiswa/listdata') }}");
    })
</script>
@stop 