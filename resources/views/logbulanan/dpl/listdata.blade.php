@extends('layouts.app')
@section('title', 'Log Bulanan Mahasiswa')
@section('container')
<x-page-header title="Log Bulanan Mahasiswa" subtitle="Data {{ request()->route('email') }}" />

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12 table-responsive">
                <x-table
                    thead-class=""
                    ajax="{{ route('admlogbulanan.listdataserver', request()->route('email')) }}"
                    :columns="[
                        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'searchable' => false],
                        ['data' => 'nama_bulan', 'name' => 'nama_bulan', 'className' => 'text-center'],
                        ['data' => 'deskripsi', 'name' => 'deskripsi'],
                        ['data' => 'action', 'name' => 'action', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                    ]"
                    :strip="[2]"
                >
                                    <x-slot:thead>
                                        <tr>
                                            <th width="1" class="text-center">No</th>
                                            <th class="text-center">Bulan</th>
                                            <th class="text-center">Deskripsi</th>
                                            <th class="text-center">Nilai</th>
                                        </tr>
                                    </x-slot:thead>
                </x-table>
            </div>
            <hr>
        </div>
        <x-button.export url="{{ url('admlogbulanan/export/' . request()->route('email')) }}" />
    </div>
</div>
@stop
