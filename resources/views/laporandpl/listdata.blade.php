@extends('layouts.app')
@section('title', 'Laporan DPL')
@section('container')
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
                            <th>Bulan</th>
                            <th>Deskripsi</th>
                            {{-- <th width="1">Aksi</th> --}}
                        </tr>
                    </x-slot:thead>
                </x-datatable>
            </div>
        </div>

        <x-btn-export url="{{ url('admlaporandpl/export/' . request()->route('email')) }}" />
    </div>
</div>

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: false,
        processing: true,
        serverSide: true,
        ajax: "{{ route('admlaporandpl.listdataserver', request()->route('email')) }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex' , className: 'text-center', orderable: false, searchable: false},
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
            // {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false, visible:false},
        ],
    });
  });
</script>
@stop