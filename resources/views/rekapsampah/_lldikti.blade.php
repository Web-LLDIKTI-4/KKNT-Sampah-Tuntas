{{-- Tabel rekap format LLDIKTI (A–L); Bulan & Kecamatan di-merge per grup. Struktur data menunggu CONTRACT.md --}}
@php
    $angka = fn ($v) => number_format((float) $v, 0, ',', '.');
    $berat = fn ($v) => number_format((float) $v, 2, ',', '.');
    $persen = fn ($v, $pembagi) => $pembagi > 0 ? number_format((float) $v, 2, ',', '.').'%' : '-';
@endphp
<div class="table-responsive scroll-box scroll-box-lg">
    <table class="table table-sm table-bordered mb-0 align-middle">
        <thead class="text-center align-middle">
            <tr>
                <th>Bulan</th>
                <th>Nama Kecamatan</th>
                <th>Nama Desa/Kelurahan</th>
                <th>Jumlah Rumah Keseluruhan</th>
                <th>Jumlah Rumah yang memilah</th>
                <th>Persentase Ketaatan Pemilahan [(E/D)*100%]</th>
                <th>Jumlah Sampah Organik Terkelola (Kg)</th>
                <th>Jumlah Sampah Anorganik Terkelola (Kg)</th>
                <th>Jumlah Sampah Residu (Kg)</th>
                <th>Total Sampah Terkelola (Kg) [G+H]</th>
                <th>Total Sampah Dihasilkan (Kg) [G+H+I]</th>
                <th>Persentase Penurunan Sampah [(J/K)*100%]</th>
            </tr>
            <tr class="small text-muted">
                @foreach (range('A', 'L') as $kol)
                    <th class="fw-normal">{{ $kol }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rekap as $grupBulan)
                @php($barisBulan = $grupBulan->kecamatan->sum(fn ($k) => $k->desa->count()))
                @foreach ($grupBulan->kecamatan as $kec)
                    @foreach ($kec->desa as $row)
                        <tr>
                            @if ($loop->parent->first && $loop->first)
                                <td rowspan="{{ $barisBulan }}" class="fw-medium align-top text-nowrap">{{ $grupBulan->nama_bulan }}</td>
                            @endif
                            @if ($loop->first)
                                <td rowspan="{{ $kec->desa->count() }}" class="fw-medium align-top">{{ $kec->kecamatan ?? '-' }}</td>
                            @endif
                            <td>{{ $row->desa ?? '-' }}</td>
                            <td class="text-end">{{ $angka($row->jml_rumah) }}</td>
                            <td class="text-end">{{ $angka($row->jml_rumah_memilah) }}</td>
                            <td class="text-end">{{ $persen($row->persen_ketaatan, $row->jml_rumah) }}</td>
                            <td class="text-end">{{ $berat($row->organik) }}</td>
                            <td class="text-end">{{ $berat($row->anorganik) }}</td>
                            <td class="text-end">{{ $berat($row->residu) }}</td>
                            <td class="text-end">{{ $berat($row->total_terkelola) }}</td>
                            <td class="text-end">{{ $berat($row->total_dihasilkan) }}</td>
                            <td class="text-end fw-medium">{{ $persen($row->persen_penurunan, $row->total_dihasilkan) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @empty
                <tr><td colspan="12" class="text-center text-muted">Belum ada data Pendataan Sampah Penduduk</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
