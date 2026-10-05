@extends('layouts.app')
@section('title', 'Log Harian')
@section('container')

<x-page-header title="Log Harian" subtitle="Data Log Harian" />

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12 table-responsive">
                <table class="table table-bordered table-sm" id="dataTable">
                    <thead>
                        <tr>
                            <th width="1">No</th>
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
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <x-button.export url="{{ url('admlogkegiatan/export/'.request()->route('email')) }}" />
    </div>
</div>


<script type="text/javascript">
    $(function () {
        var table = $('#dataTable').DataTable({
            searching: true,
            lengthChange: true,
            processing: true,
            serverSide: true,
            ajax: "{{ route('admlogkegiatan.listdataserver', request()->route('email')) }}",
            language: {
                search: "",
                searchPlaceholder: "Cari...",
                zeroRecords: "Tidak ada data yang tersedia",
                infoEmpty: "Tidak ada data yang ditemukan",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', searchable: false},
                {data: 'tanggal', name: 'tanggal', className: 'text-center'},
                {data: 'nama_kepala_keluarga', name: 'nama_kepala_keluarga'},
                {data: 'alamat_rumah', name: 'alamat_rumah'},
                {data: 'rt', name: 'rt', className: 'text-center'},
                {data: 'rw', name: 'rw', className: 'text-center'},
                {data: 'memilah', name: 'memilah', className: 'text-center', searchable: false, render: function (data) { return data == 1 ? 'Ya' : 'Tidak'; }},
                {data: 'organik_kg', name: 'organik_kg', className: 'text-end', searchable: false},
                {data: 'anorganik_kg', name: 'anorganik_kg', className: 'text-end', searchable: false},
                {data: 'residu_kg', name: 'residu_kg', className: 'text-end', searchable: false},
            ]
        });
    });
</script>
@stop