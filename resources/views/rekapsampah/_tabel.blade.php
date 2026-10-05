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
        <div class="small text-muted">Total Pengolahan</div>
        <div class="fw-medium">{{ $berat($total->total_pengurangan) }} kg</div>
    </div>
    <div>
        <div class="small text-muted">Belum Terkelola</div>
        <div class="fw-medium">{{ $berat($total->total_belum_terkelola) }} kg</div>
    </div>
</div>

<div class="table-responsive scroll-box scroll-box-lg">
    <table class="table table-sm table-bordered mb-0 align-middle">
        <thead class="text-center">
            {{-- Header bertingkat mengikuti format Excel --}}
            <tr>
                <th rowspan="3">Kecamatan</th>
                <th rowspan="3">Kelurahan</th>
                <th rowspan="3">Perguruan Tinggi</th>
                <th rowspan="3">Jumlah RW</th>
                <th rowspan="3">Penduduk (Jiwa)</th>
                <th colspan="3">Rumah</th>
                <th rowspan="3">Timbulan (Kg/Bulan)</th>
                <th colspan="12">Jenis Sampah yang Diolah</th>
                <th rowspan="3">% Penurunan Sampah</th>
                <th rowspan="3">Keterangan</th>
            </tr>
            <tr>
                <th rowspan="2">Keseluruhan</th>
                <th rowspan="2">Memilah</th>
                <th rowspan="2">% Ketaatan</th>
                <th colspan="6">Organik</th>
                <th colspan="4">Anorganik</th>
                <th rowspan="2">Total Pengolahan (Kg/Bulan)</th>
                <th rowspan="2">Belum Terkelola (Kg/Bulan)</th>
            </tr>
            <tr>
                <th>Diolah di Sumber (Kg)</th>
                <th>Metode</th>
                <th>Unit</th>
                <th>Diolah DLH (Kg)</th>
                <th>Fasilitas DLH</th>
                <th>Lokasi</th>
                <th>Diolah di Sumber (Kg)</th>
                <th>Metode</th>
                <th>Lokasi</th>
                <th>Unit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($perBulan as $grupBulan)
                <tr class="table-light">
                    <th colspan="25">{{ $namaBulan($grupBulan->bulan) }}</th>
                </tr>
                @foreach ($grupBulan->kecamatan as $kec)
                    @foreach ($kec->rows as $row)
                        <tr>
                            @if ($loop->first)
                                <td rowspan="{{ $kec->rows->count() + 1 }}" class="fw-medium align-top">{{ $kec->kecamatan ?? '-' }}</td>
                            @endif
                            <td>{{ $row->desa }}</td>
                            <td>{{ $row->nama_pt ?? '-' }}@isset($row->email)<div class="small text-muted">{{ $row->nama_ketua ?? $row->email }}</div>@endisset</td>
                            <td class="text-end">{{ $angka($row->jml_rw) }}</td>
                            <td class="text-end">{{ $angka($row->jml_penduduk) }}</td>
                            <td class="text-end">{{ $angka($row->jml_rumah) }}</td>
                            <td class="text-end">{{ $angka($row->jml_rumah_memilah) }}</td>
                            <td class="text-end">{{ $persen($row->persen_ketaatan) }}</td>
                            <td class="text-end">{{ $berat($row->timbulan) }}</td>
                            <td class="text-end">{{ $berat($row->organik_sumber) }}</td>
                            <td>{{ $row->organik_metode ?? '-' }}</td>
                            <td class="text-end">{{ $angka($row->organik_metode_unit) }}</td>
                            <td class="text-end">{{ $berat($row->organik_dlh) }}</td>
                            <td>{{ $row->organik_dlh_fasilitas ?? '-' }}</td>
                            <td>{{ $row->organik_dlh_lokasi ?? '-' }}</td>
                            <td class="text-end">{{ $berat($row->anorganik_sumber) }}</td>
                            <td>{{ $row->anorganik_metode ?? '-' }}</td>
                            <td>{{ $row->anorganik_metode_lokasi ?? '-' }}</td>
                            <td class="text-end">{{ $angka($row->anorganik_metode_unit) }}</td>
                            <td class="text-end">{{ $berat($row->pengurangan) }}</td>
                            <td class="text-end">{{ $berat($row->belum_terkelola) }}</td>
                            <td class="text-center {{ $sel($row->persen_pengurangan) }}">{{ $persen($row->persen_pengurangan) }}</td>
                            <td>{{ $row->keterangan ?? '-' }}</td>
                        </tr>
                    @endforeach
                    @if ($t = $kec->total)
                        <tr class="fw-semibold bg-lighter">
                            <td colspan="2">Total Kecamatan ({{ $angka($t->jml_kelurahan) }} kelurahan)</td>
                            <td class="text-end">{{ $angka($t->total_jml_rw) }}</td>
                            <td class="text-end">{{ $angka($t->total_jml_penduduk) }}</td>
                            <td class="text-end">{{ $angka($t->total_jml_rumah) }}</td>
                            <td class="text-end">{{ $angka($t->total_jml_rumah_memilah) }}</td>
                            <td class="text-end">{{ $persen($t->persen_ketaatan) }}</td>
                            <td class="text-end">{{ $berat($t->total_timbulan) }}</td>
                            <td class="text-end">{{ $berat($t->total_organik_sumber) }}</td>
                            <td></td>
                            <td class="text-end">{{ $angka($t->total_organik_metode_unit) }}</td>
                            <td class="text-end">{{ $berat($t->total_organik_dlh) }}</td>
                            <td></td>
                            <td></td>
                            <td class="text-end">{{ $berat($t->total_anorganik_sumber) }}</td>
                            <td></td>
                            <td></td>
                            <td class="text-end">{{ $angka($t->total_anorganik_metode_unit) }}</td>
                            <td class="text-end">{{ $berat($t->total_pengurangan) }}</td>
                            <td class="text-end">{{ $berat($t->total_belum_terkelola) }}</td>
                            <td class="text-center {{ $sel($t->persen_pengurangan) }}">{{ $persen($t->persen_pengurangan) }}</td>
                            <td></td>
                        </tr>
                    @endif
                @endforeach
            @empty
                <tr><td colspan="25" class="text-center text-muted">Belum ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
