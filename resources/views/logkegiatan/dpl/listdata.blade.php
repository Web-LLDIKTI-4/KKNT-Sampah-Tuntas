@extends('layouts.app')
@section('title', 'Log Harian')
@section('container')

<x-page-header title="Log Harian" subtitle="Data Log Harian" />

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12 table-responsive">
                <x-table
                    thead-class=""
                    ajax="{{ route('admlogkegiatan.listdataserver', request()->route('email')) }}"
                    :wrap="[2]"
                    :columns="[
                        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                        ['data' => 'tanggal', 'name' => 'tanggal', 'className' => 'text-center'],
                        ['data' => 'deskripsi', 'name' => 'deskripsi'],
                        ['data' => 'volume', 'name' => 'volume', 'className' => 'text-center'],
                        ['data' => 'satuan', 'name' => 'satuan', 'className' => 'text-center'],
                        ['data' => 'nama_kpi', 'name' => 'nama_kpi'],
                    ]"
                >
                    <x-slot:thead>
                        <tr>
                            <th width="1">No</th>
                            <th width="100">Tanggal</th>
                            <th>Deskripsi</th>
                            <th>Volume</th>
                            <th>Satuan</th>
                            <th>KPI</th>
                        </tr>
                    </x-slot:thead>
                </x-table>
            </div>
        </div>
        <x-button.export url="{{ url('admlogkegiatan/export/'.request()->route('email')) }}" />
    </div>
</div>
@stop