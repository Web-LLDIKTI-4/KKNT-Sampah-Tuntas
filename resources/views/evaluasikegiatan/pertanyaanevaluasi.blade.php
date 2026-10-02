@extends('layouts.app')
@section('title','Evaluasi Kegiatan')
@section('container')
<x-crud-index :list-url="url('admevaluasikegiatan/pertanyaanevaluasilistdata')" :add-url="url('admevaluasikegiatan/tambah')" add-title="Tambah Data Pertanyaan">
    <x-slot:actions>
        @include('evaluasikegiatan._nav', ['active' => 'pertanyaan'])
    </x-slot:actions>
</x-crud-index>
@stop
