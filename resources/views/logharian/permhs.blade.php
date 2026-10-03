@extends('layouts.app')
@section('title','Kegiatan Mahasiswa')
@section('container')

<x-page-header /> 

<div class="card">
    <div class="card-body">
        <div class="row">
    <div class="col-12 table-responsive">
        <x-table
            table-class="table table-bordered user_datatable"
            thead-class=""
            ajax="{{ url('admlogharian/permhsserver') }}/{{$email}}"
            :columns="[
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                ['data' => 'tanggal', 'name' => 'tanggal'],
                ['data' => 'deskripsi', 'name' => 'deskripsi'],
            ]"
            :wrap-text="[2]"
        >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                        </tr>
                    </x-slot:thead>
        </x-table>
    </div>
</div>
    </div>
</div>
@stop
