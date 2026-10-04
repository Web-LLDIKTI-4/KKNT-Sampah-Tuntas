@extends('layouts.app')
@section('title', 'Dashboard KPI')
@section('container')
@php
    $num = fn ($v) => number_format($v, 0, ',', '.');
@endphp

<x-page-header
    icon="ri-line-chart-line"
    :title="$isPt ? 'Dashboard KPI Perguruan Tinggi' : 'Dashboard KPI'"
    :subtitle="'Capaian KPI diukur dari persentase pengurangan sampah per bulan; target terpenuhi bila ≥ '.(int) \App\Models\Kpisampah::TARGET_PENGURANGAN.'%.'" />

<div class="card mb-6">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Ringkasan</h5>
        @if (in_array(auth()->user()->role, ['admin', 'kepala'], true))
            <x-button.export :url="route('rekapsampah.export')" id="kpi-export" label="Export" size="sm" class="text-nowrap" />
        @endif
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 text-nowrap">
                <thead>
                    <tr>
                        @unless ($isPt)
                            <th class="text-center">Jumlah Perguruan Tinggi</th>
                        @endunless
                        <th class="text-center">Kecamatan</th>
                        <th class="text-center">Kelurahan</th>
                        <th class="text-center">Mahasiswa</th>
                        <th class="text-center">DPL</th>
                        <th class="text-center">Kelompok</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @unless ($isPt)
                            <td class="text-center">{{ $num($summary['jumlah_pt']) }}</td>
                        @endunless
                        <td class="text-center">{{ $num($summary['total_kecamatan']) }}</td>
                        <td class="text-center">{{ $num($summary['total_kelurahan']) }}</td>
                        <td class="text-center">{{ $num($summary['total_mahasiswa']) }}</td>
                        <td class="text-center">{{ $num($summary['total_dpl']) }}</td>
                        <td class="text-center">{{ $num($summary['total_kelompok']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-1">Laporan Kegiatan</h5>
        <p class="mb-0 card-subtitle">Klik nama kecamatan lalu kelurahan untuk melihat kelompok dan detail capaian KPI-nya</p>
    </div>
    <div class="card-body">
        <div data-drilldown="{{ route('dashboardkpi') }}">
            @include('laporan._drilldown', $laporan)
        </div>
    </div>
</div>

<script src="{{ asset('js/drilldown.js') }}?v={{ filemtime(public_path('js/drilldown.js')) }}"></script>
@stop
