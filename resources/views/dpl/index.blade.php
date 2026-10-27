@extends('layouts.app')
@section('title','DPL')
@section('container')
<x-page-header subtitle="Daftar {{ $__env->yieldContent('title') }}" />

<div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3">
        <x-button modal="{{ url('dpl/import') }}" title="Import Data" icon="ri-chat-upload-fill">Import Data DPL
        </x-button>
        <x-button.export url="{{ route('dpl.export') }}" table="#dataTable" data-export-state />
    </div>
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        loadPartial('#resultcontent', "{{ url('dpl/listdata') }}");
    })
</script>
@stop 