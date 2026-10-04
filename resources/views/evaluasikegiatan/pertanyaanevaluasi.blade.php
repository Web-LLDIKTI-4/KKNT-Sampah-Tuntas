@extends('layouts.app')
@section('title','Evaluasi Kegiatan')
@section('container')
<x-crud-index :list-url="url('admevaluasikegiatan/pertanyaanevaluasilistdata')">
    <x-slot:actions>
        @include('evaluasikegiatan._nav', ['active' => 'pertanyaan'])
        <x-button modal="{{ url('admevaluasikegiatan/tambah') }}" title="Tambah Pertanyaan" icon="ri-add-line">Tambah Data Pertanyaan</x-button>
    </x-slot:actions>
</x-crud-index>
@stop
