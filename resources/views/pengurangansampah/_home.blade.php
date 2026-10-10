@php
    $lokasi = $penguranganSampahHome['lokasi'];
    $perPt = $penguranganSampahHome['perPt'];
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
            <div class="card-header">
                <h5 class="mb-1">Laporan Kegiatan</h5>
                <p class="mb-0 card-subtitle">Capaian = persentase pengurangan sampah per bulan; klik kecamatan lalu kelurahan untuk melihat kelompok dan detail capaiannya</p>
            </div>
            <div class="card-body">
                {{-- Dimuat lewat AJAX dari Dashboard Pengurangan Sampah agar tidak memperlambat halaman home --}}
                <div data-drilldown="{{ route('dashboard-pengurangan-sampah') }}"></div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Data Sampah per Perguruan Tinggi</h5>
                <p class="mb-0 card-subtitle">Data bulanan tiap kelurahan beserta total per kecamatan; pilih bulan, kecamatan{{ $perPt ? ', atau perguruan tinggi' : '' }}</p>
            </div>
            <div class="card-body">
                <div data-filter-host="{{ route('rekapsampah') }}"></div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/drilldown.js') }}?v={{ filemtime(public_path('js/drilldown.js')) }}"></script>
