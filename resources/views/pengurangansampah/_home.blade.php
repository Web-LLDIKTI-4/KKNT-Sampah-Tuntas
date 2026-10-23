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
                    <table class="table table-sm table-bordered mb-0 text-nowrap" data-sortable>
                        <thead>
                            <tr>
                                <th><x-button.sort>Lokasi Kegiatan</x-button.sort></th>
                                <th class="text-center"><x-button.sort type="num">Kecamatan</x-button.sort></th>
                                <th class="text-center"><x-button.sort type="num">Kelurahan/Desa</x-button.sort></th>
                                @if ($perPt)
                                    <th class="text-center"><x-button.sort type="num">Perguruan Tinggi</x-button.sort></th>
                                @endif
                                <th class="text-center"><x-button.sort type="num">Mahasiswa</x-button.sort></th>
                                <th class="text-center"><x-button.sort type="num">DPL</x-button.sort></th>
                                <th class="text-center"><x-button.sort type="num">Kelompok</x-button.sort></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lokasi['rows'] as $row)
                                <tr>
                                    <td>{{ $row['lokasi'] }}</td>
                                    <td class="text-center" data-sort-value="{{ (int) $row['kecamatan'] }}">{{ $num($row['kecamatan']) }}</td>
                                    <td class="text-center" data-sort-value="{{ (int) $row['kelurahan'] }}">{{ $num($row['kelurahan']) }}</td>
                                    @if ($perPt)
                                        <td class="text-center" data-sort-value="{{ (int) $row['pt'] }}">{{ $num($row['pt']) }}</td>
                                    @endif
                                    <td class="text-center" data-sort-value="{{ (int) $row['mahasiswa'] }}">{{ $num($row['mahasiswa']) }}</td>
                                    <td class="text-center" data-sort-value="{{ (int) $row['dpl'] }}">{{ $num($row['dpl']) }}</td>
                                    <td class="text-center" data-sort-value="{{ (int) $row['kelompok'] }}">{{ $num($row['kelompok']) }}</td>
                                </tr>
                            @empty
                                <tr data-sort-fixed><td colspan="{{ $perPt ? 7 : 6 }}" class="text-center text-muted">Belum ada mahasiswa yang ditempatkan</td></tr>
                            @endforelse
                            <tr data-sort-fixed>
                                <td class="text-muted">Belum memilih lokasi</td>
                                @if ($perPt)
                                    <td class="text-center">-</td>
                                @endif
                                <td class="text-center">-</td>
                                <td class="text-center">-</td>
                                <td class="text-center">{{ $num($lokasi['belum_lokasi']) }}</td>
                                <td class="text-center">{{ $num($lokasi['belum_lokasi_dpl']) }}</td>
                                <td class="text-center">-</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="fw-medium">
                                <td>Total</td>
                                <td class="text-center">{{ $num($lokasi['total']['kecamatan']) }}</td>
                                <td class="text-center">{{ $num($lokasi['total']['kelurahan']) }}</td>
                                @if ($perPt)
                                    <td class="text-center">{{ $num($lokasi['total']['pt']) }}</td>
                                @endif
                                <td class="text-center">{{ $num($lokasi['total']['mahasiswa']) }}</td>
                                <td class="text-center">{{ $num($lokasi['total']['dpl']) }}</td>
                                <td class="text-center">{{ $num($lokasi['total']['kelompok']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <small class="d-block mt-2 text-muted">Total = seluruh data terdaftar (sama dengan halaman login); baris per lokasi hanya menghitung data yang sudah ditempatkan. Total {{ $perPt ? 'PT/' : '' }}kecamatan/desa dihitung unik, jadi tidak sama dengan jumlah baris.</small>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-1">Capaian Pengurangan Timbulan Sampah</h5>
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
                <h5 class="mb-1">Data Sampah</h5>
                <p class="mb-0 card-subtitle">Detail per log pendataan dengan subtotal per lokasi program dan bulan; pilih bulan, kecamatan, lokasi program{{ $perPt ? ', atau perguruan tinggi' : '' }}</p>
            </div>
            <div class="card-body">
                <div data-filter-host="{{ route('rekapsampah') }}"></div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/drilldown.js') }}?v={{ filemtime(public_path('js/drilldown.js')) }}"></script>
