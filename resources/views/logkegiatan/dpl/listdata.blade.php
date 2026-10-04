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
                            <th width="100">Tanggal</th>
                            <th>Deskripsi</th>
                            <th>Volume</th>
                            <th>Satuan</th>
                            <th>KPI</th>
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
                {
                    data: 'deskripsi',
                    name: 'deskripsi',
                    render: function (data, type, row) {
                        // Ambil teks lewat DOMParser (inert), lalu escape ulang saat dirender
                        var strippedText = new DOMParser().parseFromString(data || '', 'text/html').body.textContent || '';
                        return $('<div></div>').text(strippedText).html();
                    }
                },
                {data: 'volume', name: 'volume', className: 'text-center'},
                {data: 'satuan', name: 'satuan', className: 'text-center'},
                {data: 'nama_kpi', name: 'nama_kpi'},
            ]
        });
    });
</script>
@stop