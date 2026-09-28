@extends('layouts.app')
@section('title', 'Dashboard KPI')
@section('container')
@php
    $angka = fn ($v) => \App\Models\Kpicapaian::formatAngka($v);
    $badge = fn ($p) => $p >= 100 ? 'bg-label-success' : ($p >= 50 ? 'bg-label-warning' : 'bg-label-danger');
    $namaLokasi = $filter['lokasi'] ? ($lokasiList->firstWhere('id', $filter['lokasi'])->nama_lokasi ?? '-') : 'Semua Lokasi';
@endphp

<x-page-header
    icon="ri-line-chart-line"
    :title="$isPt ? 'Dashboard KPI Perguruan Tinggi' : 'Dashboard KPI'"
    subtitle="Rekap capaian KPI ketua kelompok. Kelompok yang belum mengisi dihitung 0%." />

<div class="card mb-6">
    <div class="card-body">
        <form method="GET" action="{{ route('dashboardkpi') }}" class="row g-4 align-items-end">
            @unless ($isPt)
                <div class="col-md-3">
                    <label class="form-label" for="f-lokasi">Lokasi</label>
                    <select name="lokasi" id="f-lokasi" class="form-select form-select-sm">
                        <option value="">Semua Lokasi</option>
                        @foreach ($lokasiList as $item)
                            <option value="{{ $item->id }}" @selected($filter['lokasi'] === $item->id)>{{ $item->nama_lokasi }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="f-pt">Perguruan Tinggi</label>
                    <select name="kodept" id="f-pt" class="form-select form-select-sm">
                        <option value="">Semua PT</option>
                        @foreach ($ptList as $item)
                            <option value="{{ $item->npsn }}" @selected($filter['kodept'] === $item->npsn)>{{ $item->nm_lemb }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <div class="col-md-4">
                <label class="form-label" for="f-kegiatan">Kegiatan</label>
                <select name="id_target" id="f-kegiatan" class="form-select form-select-sm">
                    <option value="">Semua Kegiatan</option>
                    @foreach ($kegiatanList as $item)
                        <option value="{{ $item->id_target }}" @selected($filter['id_target'] === $item->id_target)>
                            {{ $item->kpi->nama_kpi ?? '-' }} | {{ $item->tahapan }} | {{ $item->nama_kpitarget }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Tampilkan</button>
                <a href="{{ route('dashboardkpi') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-6 mb-6">
    <div class="col-lg-4 self-start">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-1">Ringkasan</h5>
                <p class="mb-0 card-subtitle">{{ $namaLokasi }}</p>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-0">
                    <tbody>
                        @unless ($isPt)
                            <tr><td>Jumlah PT</td><td class="text-end">{{ number_format($summary['jumlah_pt'], 0, ',', '.') }}</td></tr>
                        @endunless
                        <tr><td>Lokasi Kegiatan</td><td class="text-end">{{ $namaLokasi }}</td></tr>
                        <tr><td>Total Kecamatan</td><td class="text-end">{{ number_format($summary['total_kecamatan'], 0, ',', '.') }}</td></tr>
                        <tr><td>Total Kelurahan</td><td class="text-end">{{ number_format($summary['total_kelurahan'], 0, ',', '.') }}</td></tr>
                        <tr><td>Total Mahasiswa</td><td class="text-end">{{ number_format($summary['total_mahasiswa'], 0, ',', '.') }}</td></tr>
                        <tr><td>Total Kelompok</td><td class="text-end">{{ number_format($summary['total_kelompok'], 0, ',', '.') }}</td></tr>
                        <tr>
                            <td class="fw-medium">Rata-rata Capaian KPI</td>
                            <td class="text-end"><span class="badge rounded-pill {{ $badge($summary['rata_capaian']) }}">{{ $angka($summary['rata_capaian']) }}%</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-1">Capaian per Perguruan Tinggi</h5>
                <p class="mb-0 card-subtitle">Realisasi dan capaian = rata-rata seluruh kelompok PT</p>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-bordered" id="tabel-rekap-pt">
                    <thead>
                        <tr>
                            <th>Kampus</th>
                            <th class="text-center">Mahasiswa</th>
                            <th class="text-center">Kelompok</th>
                            <th>KPI</th>
                            <th>Kegiatan</th>
                            <th class="text-center">Target</th>
                            <th class="text-center">Realisasi</th>
                            <th class="text-center">Mengisi</th>
                            <th class="text-center">Capaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rekapPerPt as $row)
                            <tr>
                                <td>{{ $row->nama_pt }}</td>
                                <td class="text-center">{{ number_format($row->jumlah_mahasiswa, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $row->jumlah_kelompok }}</td>
                                <td>{{ $row->nama_kpi }}</td>
                                <td>{{ $row->tahapan }} | {{ $row->nama_kpitarget }}</td>
                                <td class="text-center text-nowrap">{{ $angka($row->target) }} {{ $row->satuan }}</td>
                                <td class="text-center text-nowrap">{{ $angka($row->realisasi) }} {{ $row->satuan }}</td>
                                <td class="text-center">{{ $row->jumlah_mengisi }}/{{ $row->jumlah_kelompok }}</td>
                                <td class="text-center" data-order="{{ $row->capaian }}"><span class="badge rounded-pill {{ $badge($row->capaian) }}">{{ $angka($row->capaian) }}%</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if ($filter['kodept'])
    <div class="card">
        <div class="card-header">
            <h5 class="mb-1">Isian Capaian KPI oleh Ketua Kelompok</h5>
            <p class="mb-0 card-subtitle">Baris tanpa realisasi = belum diisi (dihitung 0%)</p>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered" id="tabel-isian">
                <thead>
                    <tr>
                        <th>Ketua Kelompok</th>
                        <th>Lokasi Kegiatan</th>
                        <th>Kecamatan</th>
                        <th>Kelurahan</th>
                        <th>KPI</th>
                        <th>Kegiatan</th>
                        <th class="text-center">Target</th>
                        <th class="text-center">Realisasi</th>
                        <th class="text-center">Capaian</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($isian as $row)
                        <tr>
                            <td>{{ $row->nama ?: $row->email }}</td>
                            <td>{{ $row->nama_lokasi ?? '-' }}</td>
                            <td>{{ $row->kecamatan ?? '-' }}</td>
                            <td>{{ $row->desa ?? '-' }}</td>
                            <td>{{ $row->nama_kpi }}</td>
                            <td>{{ $row->nama_kpitarget }}</td>
                            <td class="text-center text-nowrap">{{ $angka($row->target) }} {{ $row->satuan }}</td>
                            <td class="text-center text-nowrap">
                                @if ($row->realisasi === null)
                                    <span class="badge bg-label-secondary">Belum diisi</span>
                                @else
                                    {{ $angka($row->realisasi) }} {{ $row->satuan }}
                                @endif
                            </td>
                            <td class="text-center" data-order="{{ $row->capaian }}"><span class="badge rounded-pill {{ $badge($row->capaian) }}">{{ $angka($row->capaian) }}%</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<script>
$(function () {
    var opsi = {
        pageLength: 10,
        language: {
            search: '',
            searchPlaceholder: 'Cari...',
            zeroRecords: 'Tidak ada data yang tersedia',
            emptyTable: 'Tidak ada data yang tersedia',
            infoEmpty: 'Tidak ada data yang ditemukan',
        },
    };
    $('#tabel-rekap-pt').DataTable(opsi);
    $('#tabel-isian').DataTable(opsi);
});
</script>
@stop
