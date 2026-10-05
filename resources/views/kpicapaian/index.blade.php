@extends('layouts.app')
@section('title','Capaian KPI')
@section('container')
<x-crud-index
    title="Capaian Key Performance Indicator (KPI)"
    :list-url="url('kpicapaian/listdata')"
    :add-url="auth()->user()->akses === 'pjdesa' ? url('kpicapaian/tambah') : null"
    modal-size="modal-xl" />

{{-- List data sampah (diisi lewat form capaian); edit & hapus via endpoint kpisampah --}}
@if (auth()->user()->akses === 'pjdesa')
    <div class="mt-8">
        <x-crud-index
            id="resultcontent-sampah"
            title="Data Sampah Bulanan Kelurahan"
            :list-url="url('kpisampah/listdata')"
            modal-size="modal-xl" />
    </div>
    <script>
    $(function () {
        // Simpan capaian juga mengubah data sampah → muat ulang tabel sampah
        $(document).on('ajax-form:saved', 'form[data-sampah-form]:not([data-reload])', function () {
            var $table = $('#dataTableSampah');
            if ($table.length && $.fn.DataTable.isDataTable($table)) {
                $table.DataTable().ajax.reload(null, false);
            }
        });
    });
    </script>
@endif
{{-- Hitung otomatis untuk form capaian & form sampah --}}
@include('kpisampah._hitung')
@stop
