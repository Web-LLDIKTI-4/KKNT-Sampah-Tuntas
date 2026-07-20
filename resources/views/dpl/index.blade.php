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

        @if (session('import_errors'))
            <div class="alert alert-warning">
                <ul class="mb-0">
                    @foreach (session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('dpl/listdata') }}");
    })
</script>
@stop 