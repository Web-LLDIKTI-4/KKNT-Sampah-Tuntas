@extends('layouts.app')
@section('title', 'Laporan DPL')
@section('container')

<x-page-header title="Laporan DPL" subtitle="Data Laporan DPL" />

@if(Auth::user()->role == 'dpl')
    <div class="alert alert-info"> (Info DPL) Jika mahasiswa belum masuk ke daftar silahkan kelola melalui menu "<a href="{{ url('dplmentoring') }}">Kelola Data Mentoring Mahasiswa</a>"</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12 table-responsive">
                <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Tahun</th>
                            <th>Bulan</th>
                            <th>Deskripsi</th>
                            {{-- <th width="1">Aksi</th> --}}
                        </tr>
                    </x-slot:thead>
                </x-datatable>
            </div>
        </div>

        <x-button.export url="{{ url('admlaporandpl/export/' . request()->route('email')) }}" />
    </div>
</div>

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('admlaporandpl.listdataserver', request()->route('email')) }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex' , className: 'text-center', orderable: false, searchable: false},
            {data: 'tahun', name: 'tahun', className: 'text-center'},
            {data: 'nama_bulan', name: 'nama_bulan', className: 'text-center'},
            {
                data: 'deskripsi',
                name: 'deskripsi',
                render: function (data, type, row) {
                    // Ambil teks lewat DOMParser (inert), lalu escape ulang saat dirender
                    var strippedText = new DOMParser().parseFromString(data || '', 'text/html').body.textContent || '';
                    return $('<div></div>').text(strippedText).html();
                }
            },
        ],
    });
  });
</script>
@stop