@extends('layouts.app')
@section('title','Kegiatan Mahasiswa')
@section('container')

<x-page-header /> 

<div class="card">
    <div class="card-body">
        <div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered user_datatable">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>
    </div>
</div>
<script>
$(function(){
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ url('admlogharian/permhsserver') }}/{{$email}}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'tanggal', name: 'tanggal'},           
            {
                data: 'deskripsi',
                name: 'deskripsi',
                render: function (data, type, row) {
                    // DOMParser tidak mengeksekusi script/onerror; hasil teks di-escape ulang
                    var text = new DOMParser().parseFromString(data || '', 'text/html').body.textContent || '';
                    return $('<div class="text-wrap width-200"></div>').text(text).prop('outerHTML');
                }
            },
        ],
        layout: {
            top1: {
                searchPanes: {
                    viewTotal: true
                }
            }
        }
    });
})    

</script>
@stop 