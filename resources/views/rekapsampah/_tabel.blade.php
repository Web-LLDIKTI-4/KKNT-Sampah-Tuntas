{{-- Rekap pendataan pemilahan sampah format LLDIKTI; filter dimuat ulang lewat public/js/drilldown.js --}}
@php
    use App\Models\Kpisampah;
    use Illuminate\Support\Carbon;
    $berat = fn ($v) => Kpisampah::formatAngka($v, 2);
    $persen = fn ($v) => Kpisampah::formatPersen($v === null ? null : (float) $v);
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
        <div class="small text-muted">Persentase Penurunan Sampah</div>
        <x-status-pengurangan :persen="$total->persen_penurunan" class="fs-6" />
    </div>
    <div>
        <div class="small text-muted">Ketaatan Pemilahan</div>
        <div class="fw-medium">{{ $persen($total->persen_ketaatan) }}</div>
    </div>
    <div>
        <div class="small text-muted">Total Sampah Dihasilkan</div>
        <div class="fw-medium">{{ $berat($total->total_dihasilkan) }} kg</div>
    </div>
    <div>
        <div class="small text-muted">Total Sampah Terkelola</div>
        <div class="fw-medium">{{ $berat($total->total_terkelola) }} kg</div>
    </div>
    <div>
        <div class="small text-muted">Residu</div>
        <div class="fw-medium">{{ $berat($total->residu) }} kg</div>
    </div>
</div>

@include('rekapsampah._lldikti', ['rekap' => $rekap])
