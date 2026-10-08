@extends('layouts.app')
@section('title', 'Pendataan Pemilahan Sampah Penduduk')
@section('container')

<x-page-header title="Pendataan Pemilahan Sampah Penduduk" subtitle="Data pendataan mahasiswa." />

<div class="card">
    <div class="card-header">
        <x-button.export-bulan :url="route('pendataanpemilahan.export.mahasiswa', ['email' => $email])" label="Export Pendataan Sampah" />
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm" id="dataTablePendataanDetail">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama Kepala Keluarga</th>
                        <th>Alamat Rumah</th>
                        <th>RT</th>
                        <th>RW</th>
                        <th>Memilah</th>
                        <th>Organik (kg)</th>
                        <th>Anorganik (kg)</th>
                        <th>Residu (kg)</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<script>
    $(function () {
        $('#dataTablePendataanDetail').DataTable({
            searching: true,
            lengthChange: true,
            processing: true,
            serverSide: true,
            ajax: "{{ route('pendataanpemilahan.listdataserver.mahasiswa', ['email' => $email]) }}",
            language: {
                search: '',
                searchPlaceholder: 'Cari...',
                zeroRecords: 'Tidak ada data yang tersedia',
                infoEmpty: 'Tidak ada data yang ditemukan',
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
                {data: 'tanggal', name: 'tanggal', className: 'text-center'},
                {data: 'nama_kepala_keluarga', name: 'nama_kepala_keluarga'},
                {data: 'alamat_rumah', name: 'alamat_rumah'},
                {data: 'rt', name: 'rt', className: 'text-center'},
                {data: 'rw', name: 'rw', className: 'text-center'},
                {data: 'memilah', name: 'memilah', className: 'text-center'},
                {data: 'organik_kg', name: 'organik_kg', className: 'text-end'},
                {data: 'anorganik_kg', name: 'anorganik_kg', className: 'text-end'},
                {data: 'residu_kg', name: 'residu_kg', className: 'text-end'},
            ],
        });
    });
</script>
@stop
