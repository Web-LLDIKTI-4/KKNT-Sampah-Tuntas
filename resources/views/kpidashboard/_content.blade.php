@php
    $angka = fn ($v) => \App\Models\Kpicapaian::formatAngka($v);
    $badge = fn ($p) => $p >= 100 ? 'bg-label-success' : ($p >= 50 ? 'bg-label-warning' : 'bg-label-danger');
    $capaian = fn ($p) => $p === null ? '-' : '<span class="badge rounded-pill '.$badge($p).'">'.\App\Models\Kpicapaian::formatPersen($p).'</span>';
    $num = fn ($v) => number_format($v, 0, ',', '.');
    $namaLokasi = $filter['lokasi'] ? ($lokasiList->firstWhere('id', $filter['lokasi'])->nama_lokasi ?? '-') : 'Semua Lokasi';
@endphp

<div class="card mb-6">
    <div class="card-header">
        <h5 class="mb-0">Ringkasan</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 text-nowrap">
                <thead>
                    <tr>
                        @unless ($isPt)
                            <th class="text-center">Jumlah Perguruan Tinggi</th>
                        @endunless
                        <th>Lokasi Kegiatan</th>
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
                        <td>{{ $namaLokasi }}</td>
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
    @include('kpidashboard._per_kpi', ['perKpi' => $rekapPerKpi])
</div>

@unless ($isPt)
    <div class="card mb-6">
        @include('kpidashboard._per_daerah', ['perDaerah' => $rekapPerDaerah])
    </div>
@endunless

<div class="card mb-6">
    <div class="card-header">
        <h5 class="mb-1">Capaian per Perguruan Tinggi</h5>
        <p class="mb-0 card-subtitle">Realisasi = rata-rata ketua kelompok yang mengisi dan tindak lanjut Sudah Selesai; capaian maksimal 100%</p>
    </div>
    <div class="card-body">
        <table class="table table-sm table-bordered text-nowrap w-100" id="tabel-rekap-pt">
            <thead>
                <tr>
                    <th>Perguruan Tinggi</th>
                    <th class="text-center">Mahasiswa</th>
                    <th class="text-center">Kelompok</th>
                    <th>KPI</th>
                    <th>Kegiatan</th>
                    <th class="text-center">Target</th>
                    <th class="text-center">Realisasi</th>
                    <th class="text-center">Selesai</th>
                    <th class="text-center">Capaian</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rekapPerPt as $row)
                    <tr>
                        <td>{{ $row->nama_pt }}</td>
                        <td class="text-center">{{ $num($row->jumlah_mahasiswa) }}</td>
                        <td class="text-center">{{ $row->jumlah_kelompok }}</td>
                        <td>{{ $row->nama_kpi }}</td>
                        <td>{{ $row->kegiatan }}</td>
                        <td class="text-center">{{ $angka($row->target) }} {{ $row->satuan }}</td>
                        <td class="text-center">{{ $row->realisasi === null ? '-' : $angka($row->realisasi).' '.$row->satuan }}</td>
                        <td class="text-center">{{ $row->jumlah_selesai }}/{{ $row->jumlah_kelompok }}</td>
                        <td class="text-center" data-order="{{ $row->capaian ?? -1 }}">{!! $capaian($row->capaian) !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if ($filter['kodept'])
    <div class="card">
        <div class="card-header">
            <h5 class="mb-1">Isian Capaian KPI oleh Ketua Kelompok</h5>
            <p class="mb-0 card-subtitle">Capaian hanya dihitung untuk isian dengan tindak lanjut Sudah Selesai</p>
        </div>
        <div class="card-body">
            <table class="table table-sm table-bordered text-nowrap w-100" id="tabel-isian">
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
                            <td>{{ $row->kegiatan }}</td>
                            <td class="text-center">{{ $angka($row->target) }} {{ $row->satuan }}</td>
                            <td class="text-center">
                                @if ($row->realisasi === null)
                                    <span class="badge bg-label-secondary">Belum diisi</span>
                                @else
                                    {{ $angka($row->realisasi) }} {{ $row->satuan }}
                                @endif
                            </td>
                            <td class="text-center" data-order="{{ $row->capaian ?? -1 }}">{!! $capaian($row->capaian) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<script>
$(function () {
    // scrollX: hanya tabel yang bisa digeser, kontrol pencarian & paging tetap di card
    var opsi = {
        pageLength: 10,
        scrollX: true,
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
