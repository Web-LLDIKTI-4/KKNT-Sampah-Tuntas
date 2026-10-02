@extends('layouts.app')
@section('title','DPL')
@section('container')
<x-page-header subtitle="Daftar {{ $__env->yieldContent('title') }}" />

<div class="card">
    <div class="card-header">
        <x-button modal="{{ url('dpl/import') }}" title="Import Data" icon="ri-chat-upload-fill">Import Data DPL
        </x-button>
    </div>
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('dpl/listdata') }}");
    })
</script>
@stop 