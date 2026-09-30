@php
    $angka = fn ($v) => \App\Models\Kpicapaian::formatAngka($v);
    $badge = fn ($p) => $p >= 100 ? 'bg-label-success' : ($p >= 50 ? 'bg-label-warning' : 'bg-label-danger');
    $lokasi = $kpiHome['lokasi'];
    $perPt = $kpiHome['perPt'];
    $num = fn ($v) => number_format($v, 0, ',', '.');
@endphp

<div class="row g-6 mt-0">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Lokasi Kegiatan</h5>
                <p class="mb-0 card-subtitle">Berdasarkan penempatan mahasiswa</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 text-nowrap">
                        <thead>
                            <tr>
                                <th>Lokasi Kegiatan</th>
                                <th class="text-center">Kecamatan</th>
                                <th class="text-center">Kelurahan</th>
                                @if ($perPt)
                                    <th class="text-center">Perguruan Tinggi</th>
                                @endif
                                <th class="text-center">Mahasiswa</th>
                                <th class="text-center">DPL</th>
                                <th class="text-center">Kelompok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lokasi['rows'] as $row)
                                <tr>
                                    <td>{{ $row['lokasi'] }}</td>
                                    @if ($perPt)
                                        <td class="text-center">{{ $num($row['pt']) }}</td>
                                    @endif
                                    <td class="text-center">{{ $num($row['kecamatan']) }}</td>
                                    <td class="text-center">{{ $num($row['kelurahan']) }}</td>
                                    <td class="text-center">{{ $num($row['mahasiswa']) }}</td>
                                    <td class="text-center">{{ $num($row['dpl']) }}</td>
                                    <td class="text-center">{{ $num($row['kelompok']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $perPt ? 7 : 6 }}" class="text-center text-muted">Belum ada mahasiswa yang ditempatkan</td></tr>
                            @endforelse
                            <tr>
                                <td class="text-muted">Belum memilih lokasi</td>
                                @if ($perPt)
                                    <td class="text-center">-</td>
                                @endif
                                <td class="text-center">-</td>
                                <td class="text-center">-</td>
                                <td class="text-center">{{ $num($lokasi['belum_lokasi']) }}</td>
                                <td class="text-center">-</td>
                                <td class="text-center">-</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="fw-medium">
                                <td>Total</td>
                                @if ($perPt)
                                    <td class="text-center">{{ $num($lokasi['total']['pt']) }}</td>
                                @endif
                                <td class="text-center">{{ $num($lokasi['total']['kecamatan']) }}</td>
                                <td class="text-center">{{ $num($lokasi['total']['kelurahan']) }}</td>
                                <td class="text-center">{{ $num($lokasi['total']['mahasiswa']) }}</td>
                                <td class="text-center">{{ $num($lokasi['total']['dpl']) }}</td>
                                <td class="text-center">{{ $num($lokasi['total']['kelompok']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            @include('kpidashboard._per_kpi', ['perKpi' => $kpiHome['perKpi']])
        </div>
    </div>

    @if ($perPt)
        <div class="col-12">
            <div class="card">
                @include('kpidashboard._per_daerah', ['perDaerah' => $kpiHome['perDaerah']])
            </div>
        </div>
    @endif

    @include('kpidashboard._chart_kpi', ['chartKpi' => $kpiHome['chartKpi']])

    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-start gap-4">
                <div>
                    <h5 class="mb-1">Rata-rata Capaian KPI per Program</h5>
                    <p class="mb-0 card-subtitle">Per kegiatan, hanya ketua kelompok yang mengisi dan tindak lanjut Sudah Selesai; maksimal 100%</p>
                </div>
                <a href="{{ route('dashboardkpi') }}" class="btn btn-sm btn-outline-primary">Detail</a>
            </div>
            <div class="card-body">
                @if ($perPt)
                    @include('kpidashboard._per_pt', ['perPt' => $kpiHome['capaian']])
                @else
                    <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Kegiatan</th>
                            <th class="text-center">Rata-rata Capaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kpiHome['capaian'] as $row)
                            <tr>
                                <td>
                                    {{ $row->kegiatan }}
                                    <small class="d-block text-muted">{{ $row->nama_kpi }} · target {{ $angka($row->target) }} {{ $row->satuan }}</small>
                                </td>
                                <td class="text-center" data-order="{{ $row->capaian ?? -1 }}">
                                    @if ($row->capaian === null)
                                        -
                                    @else
                                        <span class="badge rounded-pill {{ $badge($row->capaian) }}">{{ \App\Models\Kpicapaian::formatPersen($row->capaian) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @if ($kpiHome['capaian']->isEmpty())
                            <tr><td colspan="2" class="text-center text-muted">Belum ada kegiatan KPI</td></tr>
                        @endif
                    </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
