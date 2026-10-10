@extends('layouts.app')
@section('title', 'Dashboard Pengurangan Sampah')
@section('container')
@php
    $num = fn ($v) => number_format($v, 0, ',', '.');
@endphp

<x-page-header
    icon="ri-line-chart-line"
    :title="$isPt ? 'Dashboard Pengurangan Sampah Perguruan Tinggi' : 'Dashboard Pengurangan Sampah'"
    :subtitle="'Capaian diukur dari persentase pengurangan sampah per bulan; target terpenuhi bila ≥ '.(int) \App\Models\PenguranganSampah::TARGET_PENGURANGAN.'%.'" />

<div class="card mb-6">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Ringkasan</h5>
        @if (auth()->user()->role === 'admin' || auth()->user()->isPemantau())
            <x-button.export :url="route('rekapsampah.export')" id="pengurangan-sampah-export" label="Export" size="sm" class="btn-filter" />
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

<div class="card mb-6">
    <div class="card-header">
        <h5 class="mb-1">Rekap Sampah per Bulan</h5>
        <p class="mb-0 card-subtitle">Dari Pendataan Sampah Penduduk · penurunan sampah {{ \App\Models\PenguranganSampah::formatPersen($total->persen_penurunan) }}</p>
    </div>
    <div class="card-body">
        @include('rekapsampah._lldikti', ['rekap' => $rekap])
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-1">Laporan Kegiatan</h5>
        <p class="mb-0 card-subtitle">Klik nama kecamatan lalu kelurahan untuk melihat kelompok dan detail capaiannya</p>
    </div>
    <div class="card-body">
        <div data-drilldown="{{ route('dashboard-pengurangan-sampah') }}">
            @include('laporan._capaian_publik', $laporan)
        </div>
    </div>
</div>

<script src="{{ asset('js/drilldown.js') }}?v={{ filemtime(public_path('js/drilldown.js')) }}"></script>
@stop
