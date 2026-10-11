@extends('layouts.app')
@section('title','Evaluasi Kegiatan')
@section('container')
<x-crud-index :list-url="url('admevaluasikegiatan/pertanyaanevaluasilistdata')">
    <x-slot:actions>
        <div class="d-flex flex-column flex-md-row align-items-center gap-3 w-100">
            @include('evaluasikegiatan._nav', ['active' => 'pertanyaan'])
            <x-button modal="{{ url('admevaluasikegiatan/tambah') }}" title="Tambah Data Pertanyaan" icon="ri-add-line">Tambah Data Pertanyaan</x-button>
        </div>
    </x-slot:actions>
</x-crud-index>
@stop
