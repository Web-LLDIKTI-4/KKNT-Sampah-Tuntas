@extends('layouts.app')
@section('title','Evaluasi Kegiatan')
@section('container')
<x-crud-index :list-url="url('admevaluasikegiatan/pertanyaanevaluasilistdata')" :add-url="url('admevaluasikegiatan/tambah')" add-title="Tambah Data Pertanyaan">
    <x-slot:actions>
        <div class="btn-group" role="group" aria-label="Navigasi evaluasi">
            <a href="{{ url('admevaluasikegiatan') }}" class="btn btn-secondary btn-sm waves-effect waves-light">
                <i class="ri-pass-valid-line d-none d-md-block me-2"></i>
                <span>Data Hasil Evaluasi</span>
            </a>
            <a href="{{ url('admevaluasikegiatan/pertanyaanevaluasi') }}" class="btn btn-info btn-sm waves-effect waves-light">
                <i class="ri-questionnaire-line d-none d-md-block me-2"></i>
                <span>Data Pertanyaan</span>
            </a>
        </div>
    </x-slot:actions>
</x-crud-index>
@stop
