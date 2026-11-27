{{-- Data Sampah landing: satu baris per lokasi program (agregat, tanpa data per rumah) + total seluruh lokasi --}}
@php
    use App\Models\PenguranganSampah;
    use App\Services\PenguranganSampahService;
    $angka = fn ($v) => PenguranganSampah::formatAngka($v);
    $berat = fn ($v) => PenguranganSampah::formatAngka($v, 2);
    $persen = fn ($v) => PenguranganSampah::formatAngka($v, 2);
    $total = PenguranganSampahService::totalLokasi($subtotal);
    $kolom = [
        ['ri-home-4-fill', 'Jumlah Rumah Keseluruhan'],
        ['ri-home-heart-fill', 'Jumlah Rumah yang Memilah'],
        ['ri-pie-chart-2-fill', 'Persentase Rumah yang Memilah (%)'],
        ['ri-delete-bin-fill', 'Total Sampah Dihasilkan (Kg)'],
        ['ri-recycle-fill', 'Total Sampah Terkelola (Kg)'],
        ['ri-leaf-fill', 'Persentase Pengelolaan Sampah (%)'],
        ['ri-delete-bin-2-fill', 'Total Sampah Berdasarkan Jiwa (Kg)'],
        ['ri-bar-chart-box-fill', 'Persentase Penurunan Timbulan Sampah'],
    ];
    $sel = fn ($r) => [
        $angka($r->jml_rumah), $angka($r->jml_rumah_memilah), $persen($r->persen_ketaatan),
        $berat($r->total_dihasilkan), $berat($r->total_terkelola), $persen($r->persen_pengelolaan),
        $berat($r->timbulan), PenguranganSampah::formatPersen($r->persen_penurunan_timbulan),
    ];
@endphp

<form data-filter class="d-flex justify-content-end mb-3">
    <div class="ds-filter-bulan">
        <label for="dsBulan" class="form-label small mb-1">Bulan</label>
        <select name="bulan" id="dsBulan" class="form-select form-select-sm">
            <option value="semua" @selected($bulan === 'semua')>Semua Bulan</option>
            @foreach ($bulanList as $b)
                <option value="{{ $b }}" @selected($bulan === $b)>{{ \Illuminate\Support\Carbon::parse($b.'-01')->translatedFormat('F Y') }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="table-responsive ds-publik">
    <table class="table mb-0 align-middle text-center">
        <thead>
            <tr>
                <th scope="col" class="ds-wilayah"><i class="ri-map-pin-2-fill d-block" aria-hidden="true"></i>Kewilayahan</th>
                @foreach ($kolom as [$icon, $label])
                    <th scope="col" @class(['ds-terkelola' => $loop->index === 4])>
                        <i class="{{ $icon }} d-block" aria-hidden="true"></i>{{ $label }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($subtotal as $lokasi)
                <tr>
                    <th scope="row" class="text-start text-nowrap"><i class="ri-map-pin-2-fill" aria-hidden="true"></i> {{ $lokasi->nama }}</th>
                    @foreach ($sel($lokasi->total) as $i => $nilai)
                        <td @class(['text-nowrap', 'ds-terkelola' => $i === 4, 'fw-bold' => $i === 7])>{{ $nilai }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($kolom) + 1 }}" class="text-muted py-4">Belum ada data Pendataan Sampah Penduduk</td></tr>
            @endforelse
        </tbody>
        @if ($subtotal->isNotEmpty())
            <tfoot>
                <tr>
                    <th scope="row" class="text-start text-nowrap"><i class="ri-bar-chart-2-line" aria-hidden="true"></i> Total</th>
                    @foreach ($sel($total) as $i => $nilai)
                        <td @class(['text-nowrap', 'ds-sorot' => in_array($i, [4, 7], true)])>{{ $nilai }}</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</div>
<p class="small text-muted mt-2 mb-0">Total Sampah Berdasarkan Jiwa = koefisien timbulan lokasi program × jumlah jiwa. Penurunan Timbulan = (1 − Total Sampah Dihasilkan ÷ Total Sampah Berdasarkan Jiwa) × 100%.</p>
