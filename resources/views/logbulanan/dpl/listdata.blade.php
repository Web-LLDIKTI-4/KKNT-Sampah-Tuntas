@extends('layouts.app')
@section('title', 'Log Bulanan Mahasiswa')
@section('container')
<x-page-header title="Log Bulanan Mahasiswa" subtitle="Data {{ request()->route('email') }}" />

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12 table-responsive">
                <x-datatable id="dataTable">
                    <x-slot:thead>
                        <tr>
                            <th width="1" class="text-center">No</th>
                            <th class="text-center">Bulan</th>
                            <th class="text-center">Deskripsi</th>
                            <th class="text-center">Nilai</th>
                        </tr>
                    </x-slot:thead>
                </x-datatable>
            </div>
            <hr>
        </div>
        <x-button.export url="{{ url('admlogbulanan/export/' . request()->route('email')) }}" />
    </div>
</div>

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('admlogbulanan.listdataserver', request()->route('email')) }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', searchable: false},
            {data: 'nama_bulan', name: 'nama_bulan', className: 'text-center'},
            {
                data: 'deskripsi',
                name: 'deskripsi',
                render: function (data, type, row) {
                    // Membuat sebuah div sementara untuk membersihkan tag HTML
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                    var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                    return strippedText;
                }
            },
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
        ],
        layout: {
            top1: {
                searchPanes: {
                    viewTotal: true
                }
            }
        }
    });
  });
</script>
@stop