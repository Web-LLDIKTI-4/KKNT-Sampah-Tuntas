@extends('layouts.app')
@section('title','Evaluasi Kegiatan')
@section('container')

<x-page-header /> 

<!-- Nav -->
<div class="card">
    <div class="card-header d-flex flex-column flex-md-row align-items-center gap-3">
        @if (Auth::user()->role === 'admin')
            @include('evaluasikegiatan._nav', ['active' => 'hasil'])
            <x-button modal="{{ url('admevaluasikegiatan/tambah') }}" title="Tambah Pertanyaan" icon="ri-add-line">Tambah Data Pertanyaan</x-button>
        @endif
    </div>
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>

<script>
    $(function(){
        // $("#resultcontent").load("{{ url('admevaluasikegiatan/pertanyaanevaluasilistdata') }}");
        $("#resultcontent").load("{{ url('admevaluasikegiatan/hasilevaluasi') }}");
    })
</script>
@stop 