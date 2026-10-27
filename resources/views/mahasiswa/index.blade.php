@extends('layouts.app')
@section('title','Mahasiswa')
@section('container')
<x-page-header subtitle="Daftar {{ $__env->yieldContent('title') }}" />

<div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3">
        <x-button modal="{{ url('mahasiswa/import') }}" title="Import Data" icon="ri-chat-upload-fill">Import Data Mahasiswa
        </x-button>
        <x-button.export url="{{ route('mahasiswa.export') }}" table="#dataTable" data-export-state />
    </div>
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        loadPartial('#resultcontent', "{{ url('mahasiswa/listdata') }}");
    })
</script>
@stop 