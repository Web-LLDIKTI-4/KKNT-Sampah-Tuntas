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
                            <th>Kategori Kegiatan</th>
                            <th>Nama Aktivitas</th>
                            <th>Deskripsi Kegiatan</th>
                            <th>Volume/Kuantitas Output</th>
                            <th>Satuan</th>
                            <th>Tautan Bukti</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <x-button.export url="{{ url('admlogkegiatan/export/'.rawurlencode(request()->route('email'))) }}" />
    </div>
</div>


<script type="text/javascript">
    $(function () {
        var table = $('#dataTable').DataTable({
            searching: true,
            lengthChange: true,
            processing: true,
            serverSide: true,
            searchDelay: 500,
            ajax: "{{ route('admlogkegiatan.listdataserver', request()->route('email')) }}",
            language: {
                search: "",
                searchPlaceholder: "Cari...",
                zeroRecords: "Tidak ada data yang tersedia",
                infoEmpty: "Tidak ada data yang ditemukan",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
                {data: 'tanggal', name: 'tanggal', className: 'text-center'},
                {data: 'nama_kategori', name: 'nama_kategori', orderable: false},
                {data: 'nama_aktivitas', name: 'nama_aktivitas'},
                {data: 'deskripsi', name: 'deskripsi', className: 'text-wrap'},
                {data: 'volume', name: 'volume', className: 'text-center'},
                {data: 'satuan', name: 'satuan'},
                {data: 'tautan', name: 'tautan', orderable: false, searchable: false},
            ]
        });
    });
</script>
@stop
