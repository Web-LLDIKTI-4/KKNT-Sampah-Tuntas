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
                    <th>Deskripsi Kegiatan</th>
                    <th>Volume</th>
                    <th>Satuan</th>
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
            {data: 'tanggal', name: 'tanggal', className: 'text-center'},
            {data: 'deskripsi', name: 'deskripsi'},
            {data: 'volume', name: 'volume', className: 'text-end'},
            {data: 'satuan', name: 'satuan'},
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