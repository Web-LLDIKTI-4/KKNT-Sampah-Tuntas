@extends('layouts.app')
@section('title','DPL')
@section('container')
<x-page-header subtitle="Daftar {{ $__env->yieldContent('title') }}" />

<div class="card">
    <div class="card-header">
        <x-btn-modal url="{{ url('dpl/import') }}" title="Import Data">
            <i class="ri-chat-upload-fill me-2"></i> Import Data DPL
        </x-btn-modal>
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