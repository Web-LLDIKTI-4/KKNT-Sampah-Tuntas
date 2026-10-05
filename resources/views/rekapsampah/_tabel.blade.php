{{-- Data sampah per kelurahan + total kecamatan per bulan; filter dimuat ulang lewat public/js/drilldown.js --}}
@php
    use App\Models\Kpisampah;
    use Illuminate\Support\Carbon;
    $angka = fn ($v) => Kpisampah::formatAngka($v);
    $berat = fn ($v) => Kpisampah::formatAngka($v, 2);
    $persen = fn ($v) => Kpisampah::formatPersen($v === null ? null : (float) $v);
    $sel = fn ($p) => Kpisampah::warnaSel($p);
    $namaBulan = fn ($b) => Carbon::parse($b)->translatedFormat('F Y');
    $pilihanBulan = $filter['bulan'] ?? 'semua';
@endphp
<form data-filter class="row g-3 align-items-end mb-4">
    <div class="col-md-3">
        <label class="form-label small mb-1">Bulan</label>
        <select name="bulan" class="form-select form-select-sm">
            <option value="semua" @selected($pilihanBulan === 'semua')>Semua Bulan</option>
            @foreach ($bulanList as $bulan)
                <option value="{{ $bulan }}" @selected($pilihanBulan === $bulan)>{{ $namaBulan($bulan.'-01') }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-1">Kecamatan</label>
        <select name="kecamatan" class="form-select form-select-sm">
            <option value="">Semua Kecamatan</option>
            @foreach ($kecamatanList as $item)
                <option value="{{ $item->id_kecamatan }}" @selected($filter['id_kecamatan'] === $item->id_kecamatan)>{{ $item->kecamatan }}</option>
            @endforeach
        </select>
    </div>
    @unless ($isPt)
        <div class="col-md-3">
            <label class="form-label small mb-1">Perguruan Tinggi</label>
            <select name="kodept" class="form-select form-select-sm">
                <option value="">Semua Perguruan Tinggi</option>
                @foreach ($ptList as $item)
                    <option value="{{ $item->npsn }}" @selected($filter['kodept'] === $item->npsn)>{{ $item->nm_lemb }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md">
            <label class="form-label small mb-1">Klaster</label>
            <select name="klaster" class="form-select form-select-sm">
                <option value="">Semua Klaster</option>
                @foreach (Kpisampah::KLASTER as $kode => $k)
                    <option value="{{ $kode }}" @selected($filter['klaster'] === $kode)>{{ $k['label'] }} ({{ $k['ket'] }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-auto">
            <a href="{{ route('rekapsampah.export', array_filter(['bulan' => $pilihanBulan, 'kecamatan' => $filter['id_kecamatan'], 'kodept' => $filter['kodept'], 'klaster' => $filter['klaster']])) }}"
               class="btn btn-sm btn-success btn-filter w-100" title="Export sesuai filter & klaster"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Export</a>
        </div>
    @endunless
</form>

@unless ($isPt)
    {{-- Rekap klaster PT: klik kartu untuk menampilkan PT di klaster tersebut saja --}}
    <div class="row g-3 mb-4">
        @foreach (Kpisampah::KLASTER as $kode => $k)
            @php($anggota = $klasterPt->where('klaster', $kode))
            <div class="col-md-4">
                <a href="#" data-klaster="{{ $kode }}" @class(['card h-100 text-body border', 'border-3 border-primary' => $filter['klaster'] === $kode])>
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge {{ $k['badge'] }}">{{ $k['label'] }} {{ $k['ket'] }}</span>
                            <span class="fs-4 fw-semibold">{{ $anggota->count() }} <small class="fs-6 text-muted">PT</small></span>
                        </div>
                        <div class="scroll-box-sm pe-1">
                            @forelse ($anggota as $pt)
                                <div class="d-flex justify-content-between small">
                                    <span class="text-truncate me-2">{{ $pt->nama_pt }}</span>
                                    <span class="text-nowrap">{{ $persen($pt->persen) }}</span>
                                </div>
                            @empty
                                <div class="small text-muted">Tidak ada PT</div>
                            @endforelse
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endunless

<div class="d-flex flex-wrap gap-5 mb-4">
    <div>
        <div class="small text-muted">Persentase Pengurangan Sampah</div>
        <x-status-pengurangan :persen="$total->persen_pengurangan" class="fs-6" />
    </div>
    <div>
        <div class="small text-muted">Ketaatan Pemilahan</div>
        <div class="fw-medium">{{ $persen($total->persen_ketaatan) }}</div>
    </div>
    <div>
        <div class="small text-muted">Total Timbulan</div>
        <div class="fw-medium">{{ $berat($total->total_timbulan) }} kg</div>
    </div>
    <div>
        <div class="small text-muted">Total Pengurangan</div>
        <div class="fw-medium">{{ $berat($total->total_pengurangan) }} kg</div>
    </div>
    <div>
        <div class="small text-muted">Total Residu</div>
        <div class="fw-medium">{{ $berat($total->total_residu) }} kg</div>
    </div>
    <div>
        <div class="small text-muted">Bank Sampah</div>
        <div class="fw-medium">{{ $angka($total->total_jml_bank_sampah) }}</div>
    </div>
</div>

<div class="table-responsive scroll-box scroll-box-lg">
    <table class="table table-sm table-bordered mb-0 align-middle">
        <thead class="text-center">
            <tr>
                <th rowspan="2">Kecamatan</th>
                <th rowspan="2">Kelurahan</th>
                <th rowspan="2">Perguruan Tinggi</th>
                <th colspan="2">Jumlah RW</th>
                <th colspan="3">Rumah</th>
                <th colspan="5">Sampah (kg/bulan)</th>
                <th rowspan="2">% Pengurangan Sampah</th>
                <th rowspan="2">Bank Sampah</th>
            </tr>
            <tr>
                <th>KBS DLH</th>
                <th>Non-KBS</th>
                <th>Keseluruhan</th>
                <th>Memilah</th>
                <th>% Ketaatan</th>
                <th>Timbulan</th>
                <th>Peng. Organik</th>
                <th>Peng. Anorganik</th>
                <th>Pengurangan</th>
                <th>Residu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($perBulan as $grupBulan)
                <tr class="table-light">
                    <th colspan="15">{{ $namaBulan($grupBulan->bulan) }}</th>
                </tr>
                @foreach ($grupBulan->kecamatan as $kec)
                    @foreach ($kec->rows as $row)
                        <tr>
                            @if ($loop->first)
                                <td rowspan="{{ $kec->rows->count() + 1 }}" class="fw-medium align-top">{{ $kec->kecamatan ?? '-' }}</td>
                            @endif
                            <td>{{ $row->desa }}</td>
                            <td>{{ $row->nama_pt ?? '-' }}@isset($row->email)<div class="small text-muted">{{ $row->nama_ketua ?? $row->email }}</div>@endisset</td>
                            <td class="text-end">{{ $angka($row->jml_rw_kbs) }}</td>
                            <td class="text-end">{{ $angka($row->jml_rw_non_kbs) }}</td>
                            <td class="text-end">{{ $angka($row->jml_rumah) }}</td>
                            <td class="text-end">{{ $angka($row->jml_rumah_memilah) }}</td>
                            <td class="text-end">{{ $persen($row->persen_ketaatan) }}</td>
                            <td class="text-end">{{ $berat($row->timbulan) }}</td>
                            <td class="text-end">{{ $berat($row->pengurangan_organik) }}</td>
                            <td class="text-end">{{ $berat($row->pengurangan_anorganik) }}</td>
                            <td class="text-end">{{ $berat($row->pengurangan) }}</td>
                            <td class="text-end">{{ $berat($row->residu) }}</td>
                            <td class="text-center {{ $sel($row->persen_pengurangan) }}">{{ $persen($row->persen_pengurangan) }}</td>
                            <td class="text-end">{{ $angka($row->jml_bank_sampah) }}</td>
                        </tr>
                    @endforeach
                    @if ($t = $kec->total)
                        <tr class="fw-semibold bg-lighter">
                            <td colspan="2">Total Kecamatan ({{ $angka($t->jml_kelurahan) }} kelurahan)</td>
                            <td class="text-end">{{ $angka($t->total_jml_rw_kbs) }}</td>
                            <td class="text-end">{{ $angka($t->total_jml_rw_non_kbs) }}</td>
                            <td class="text-end">{{ $angka($t->total_jml_rumah) }}</td>
                            <td class="text-end">{{ $angka($t->total_jml_rumah_memilah) }}</td>
                            <td class="text-end">{{ $persen($t->persen_ketaatan) }}</td>
                            <td class="text-end">{{ $berat($t->total_timbulan) }}</td>
                            <td class="text-end">{{ $berat($t->total_pengurangan_organik) }}</td>
                            <td class="text-end">{{ $berat($t->total_pengurangan_anorganik) }}</td>
                            <td class="text-end">{{ $berat($t->total_pengurangan) }}</td>
                            <td class="text-end">{{ $berat($t->total_residu) }}</td>
                            <td class="text-center {{ $sel($t->persen_pengurangan) }}">{{ $persen($t->persen_pengurangan) }}</td>
                            <td class="text-end">{{ $angka($t->total_jml_bank_sampah) }}</td>
                        </tr>
                    @endif
                @endforeach
            @empty
                <tr><td colspan="15" class="text-center text-muted">Belum ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
