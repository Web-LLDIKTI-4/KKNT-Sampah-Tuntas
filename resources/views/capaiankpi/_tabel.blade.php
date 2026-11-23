{{-- Filter, tabel & pagination Capaian KPI; dimuat ulang lewat public/js/drilldown.js (data-filter-host) --}}
@php
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
    <table class="table table-bordered table-sm align-middle mb-0">
        <thead data-filter-links>
            <tr>
                <th class="text-center" width="1">No</th>
                @foreach (['kab_kota' => 'Kabupaten/Kota', 'kecamatan' => 'Kecamatan', 'desa' => 'Kelurahan/Desa'] as $kolom => $label)
                    <th class="text-center" {{ $ariaSort($kolom) }}>
                        <a href="{{ $sortUrl($kolom) }}" class="table-sort text-reset text-nowrap">{{ $label }}<i class="{{ $sortIcon($kolom) }}" aria-hidden="true"></i></a>
                    </th>
                @endforeach
                <th class="text-center">Baseline</th>
                <th class="text-center">Ketaatan Pemilahan (%)</th>
                <th class="text-center">Penurunan Timbulan Sampah (%)</th>
                <th class="text-center" {{ $ariaSort('klaster') }}>
                    <a href="{{ $sortUrl('klaster') }}" class="table-sort text-reset text-nowrap">Klaster<i class="{{ $sortIcon('klaster') }}" aria-hidden="true"></i></a>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($kelurahan as $kel)
                <tr class="table-light">
                    <td class="text-center fw-medium">{{ $kelurahan->firstItem() + $loop->index }}</td>
                    <td>{{ $kel->kab_kota ?? '-' }}</td>
                    <td>{{ $kel->kecamatan ?? '-' }}</td>
                    <td class="fw-medium">{{ $kel->desa }}</td>
                    <td class="text-center text-nowrap">{{ PenguranganSampah::formatBaseline($kel->baseline, $kel->satuan_baseline) }}</td>
                    <td class="text-center">{{ $persen($kel->persen_ketaatan) }}</td>
                    <td class="text-center">{{ $persen($kel->persen) }}</td>
                    <td class="text-center">@if ($k = $klasterOf($kel->klaster))<span class="badge {{ $k['badge'] }}">{{ $k['label'] }}</span>@else - @endif</td>
                </tr>
                <tr>
                    <td></td>
                    <td colspan="7" class="py-2">
                        @if ($kel->pt->isEmpty())
                            <span class="text-muted small">Belum ada perguruan tinggi yang ditempatkan</span>
                        @else
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>PTS</th>
                                        <th class="text-center">Mahasiswa</th>
                                        <th class="text-center">DPL</th>
                                        <th class="text-center">Penurunan Timbulan Sampah (%)</th>
                                        <th class="text-center">Klaster</th>
                                        <th class="text-center" width="1">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($kel->pt as $pt)
                                        <tr>
                                            <td>{{ $pt->nama_pt }}</td>
                                            <td class="text-center">{{ PenguranganSampah::formatAngka($pt->jumlah_mahasiswa) }}</td>
                                            <td class="text-center">{{ PenguranganSampah::formatAngka($pt->jumlah_dpl) }}</td>
                                            <td class="text-center">{{ $persen($pt->persen) }}</td>
                                            <td class="text-center">@if ($k = $klasterOf($pt->klaster))<span class="badge {{ $k['badge'] }}">{{ $k['label'] }}</span>@else - @endif</td>
                                            <td class="text-center">
                                                <x-button.icon action="view" :modal="route('capaian-kpi.detail', ['desa' => $kel->id_desa, 'kodept' => $pt->kodept, 'bulan' => $bulan])"
                                                    :title="'Detail '.$pt->nama_pt.' - '.$kel->desa" :aria-label="'Detail '.$pt->nama_pt" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">
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
