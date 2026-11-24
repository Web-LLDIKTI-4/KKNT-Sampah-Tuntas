{{-- Filter, tabel & pagination Capaian KPI; dimuat ulang lewat public/js/drilldown.js (data-filter-host) --}}
@php
    use App\Http\Requests\CapaianKpiRequest;
    use App\Models\PenguranganSampah;
    use App\Services\PenguranganSampahService;
    use Illuminate\Support\Carbon;
    $persen = fn ($v) => PenguranganSampah::formatPersen($v === null ? null : (float) $v);
    $klasterOf = fn ($k) => PenguranganSampah::KLASTER[$k] ?? null;
    $semua = PenguranganSampahService::SEMUA_BULAN;
    $filterAktif = array_filter(['bulan' => $bulan, 'q' => $params['q'], 'sort' => $params['sort'], 'dir' => $params['sort'] ? $params['dir'] : null]);
    // Klik kolom baru = asc, kolom sama = toggle; sort di server agar berlaku untuk semua halaman
    $sortUrl = fn ($kolom) => route('capaian-kpi', array_merge($filterAktif, [
        'sort' => $kolom,
        'dir' => $params['sort'] === $kolom && $params['dir'] === 'asc' ? 'desc' : 'asc',
    ]));
    $sortIcon = fn ($kolom) => $params['sort'] !== $kolom ? 'ri-arrow-up-down-line' : ($params['dir'] === 'asc' ? 'ri-arrow-up-line' : 'ri-arrow-down-line');
    // aria-sort hanya di kolom aktif (laporan.css menebalkan ikonnya)
    $ariaSort = fn ($kolom) => $params['sort'] !== $kolom ? '' : 'aria-sort='.($params['dir'] === 'asc' ? 'ascending' : 'descending');
    // Label PTS: 0 → a, 25 → z, 26 → aa
    $huruf = function (int $i) {
        for ($s = '', $i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(97 + ($i - 1) % 26).$s;
        }

        return $s;
    };
@endphp
<form data-filter class="row g-3 align-items-start mb-4">
    @if ($params['sort'])
        <input type="hidden" name="sort" value="{{ $params['sort'] }}">
        <input type="hidden" name="dir" value="{{ $params['dir'] }}">
    @endif
    {{-- Kiri: filter bulan + export di bawahnya; kanan: cari --}}
    <div class="col-md-4 col-lg-3 d-flex flex-column gap-2">
        <select name="bulan" class="form-select form-select-sm" aria-label="Bulan">
            <option value="{{ $semua }}" @selected($bulan === $semua)>Semua Bulan</option>
            @foreach ($bulanList as $item)
                <option value="{{ $item }}" @selected($bulan === $item)>{{ Carbon::parse($item.'-01')->translatedFormat('F Y') }}</option>
            @endforeach
        </select>
        <a href="{{ route('capaian-kpi.export', $filterAktif) }}"
           class="btn btn-sm btn-success btn-filter align-self-start" title="Export sesuai filter & pencarian"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Export</a>
    </div>
    <div class="col-md-5 col-lg-4 ms-md-auto">
        <input type="search" name="q" value="{{ $params['q'] }}" maxlength="100" autocomplete="off" aria-label="Cari"
            class="form-control form-control-sm" placeholder="Cari kab/kota, kecamatan, kelurahan, PTS…">
    </div>
</form>

<div class="table-responsive">
    {{-- Bertingkat: Kab/Kota → Kecamatan → Kelurahan (bernomor) → PTS (a, b, …); semua nama rata kiri --}}
    <table class="table table-bordered table-sm align-middle mb-0">
        <thead data-filter-links>
            <tr>
                <th class="text-center" width="1">No</th>
                @foreach (CapaianKpiRequest::SORT as $kolom => $label)
                    <th class="text-center" {{ $ariaSort($kolom) }}>
                        <a href="{{ $sortUrl($kolom) }}" class="table-sort text-reset" title="Urutkan {{ $label }} di tiap kecamatan & kelurahan">{{ $label }}<i class="{{ $sortIcon($kolom) }}" aria-hidden="true"></i></a>
                    </th>
                @endforeach
                <th class="text-center" width="1">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @php $no = $kelurahan->firstItem(); @endphp
            @forelse ($kelurahan->getCollection()->groupBy(fn ($r) => $r->kab_kota ?? '') as $kabKota => $perKab)
                <tr class="table-warning">
                    <td colspan="10" class="fw-bold">{{ $kabKota ?: 'Kabupaten/Kota belum diatur' }}</td>
                </tr>
                @foreach ($perKab->groupBy(fn ($r) => $r->kecamatan ?? '') as $kecamatan => $perKec)
                    <tr>
                        <td colspan="10" class="fw-semibold"><span class="text-muted fw-normal">Kecamatan</span> {{ $kecamatan ?: '-' }}</td>
                    </tr>
                    @foreach ($perKec as $kel)
                        <tr class="table-light">
                            <td class="text-center fw-medium">{{ $no++ }}</td>
                            <td class="fw-medium">{{ $kel->desa }}</td>
                            <td class="text-center">{{ $persen($kel->persen_ketaatan) }}</td>
                            <td class="text-center">{{ $persen($kel->persen_pengelolaan) }}</td>
                            <td class="text-center">{{ $persen($kel->persen) }}</td>
                            <td class="text-center text-nowrap">{{ PenguranganSampah::formatBaseline($kel->baseline, $kel->satuan_baseline) }}</td>
                            <td></td>
                            <td></td>
                            <td class="text-center">@if ($k = $klasterOf($kel->klaster))<span class="badge {{ $k['badge'] }}">{{ $k['label'] }}</span>@else - @endif</td>
                            <td></td>
                        </tr>
                        @forelse ($kel->pt as $pt)
                            <tr>
                                <td></td>
                                <td>{{ $huruf($loop->index) }}. {{ $pt->nama_pt }}</td>
                                <td class="text-center">{{ $persen($pt->persen_ketaatan) }}</td>
                                <td class="text-center">{{ $persen($pt->persen_pengelolaan) }}</td>
                                <td class="text-center">{{ $persen($pt->persen) }}</td>
                                <td></td>
                                <td class="text-center">{{ PenguranganSampah::formatAngka($pt->jumlah_mahasiswa) }}</td>
                                <td class="text-center">{{ PenguranganSampah::formatAngka($pt->jumlah_dpl) }}</td>
                                <td class="text-center">@if ($k = $klasterOf($pt->klaster))<span class="badge {{ $k['badge'] }}">{{ $k['label'] }}</span>@else - @endif</td>
                                <td class="text-center">
                                    <x-button.icon action="view" :modal="route('capaian-kpi.detail', ['desa' => $kel->id_desa, 'kodept' => $pt->kodept, 'bulan' => $bulan])"
                                        :title="'Detail '.$pt->nama_pt.' - '.$kel->desa" :aria-label="'Detail '.$pt->nama_pt" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td></td>
                                <td colspan="9" class="text-muted small">Belum ada perguruan tinggi yang ditempatkan</td>
                            </tr>
                        @endforelse
                    @endforeach
                @endforeach
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">
                        {{ $params['q'] ? 'Tidak ada kelurahan/desa yang cocok dengan pencarian' : 'Belum ada kelurahan/desa dengan data pendataan sampah' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($kelurahan->hasPages())
    <div class="mt-4" data-filter-links>{{ $kelurahan->links('pagination::bootstrap-5') }}</div>
@endif
