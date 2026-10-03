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
                <x-table
                    thead-class=""
                    ajax="{{ route('admlaporandpl.listdataserver', request()->route('email')) }}"
                    :columns="[
                        ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'orderable' => false, 'searchable' => false],
                        ['data' => 'tahun', 'name' => 'tahun', 'className' => 'text-center'],
                        ['data' => 'nama_bulan', 'name' => 'nama_bulan', 'className' => 'text-center'],
                        ['data' => 'deskripsi', 'name' => 'deskripsi'],
                    ]"
                    :strip="[3]"
                >
                                    <x-slot:thead>
                                        <tr>
                                            <th width="1">No</th>
                                            <th>Tahun</th>
                                            <th>Bulan</th>
                                            <th>Deskripsi</th>
                                            {{-- <th width="1">Aksi</th> --}}
                                        </tr>
                                    </x-slot:thead>
                </x-table>
            </div>
        </div>

        <x-button.export url="{{ url('admlaporandpl/export/' . request()->route('email')) }}" />
    </div>
</div>
@stop
