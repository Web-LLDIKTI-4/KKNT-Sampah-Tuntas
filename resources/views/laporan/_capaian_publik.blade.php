{{-- Capaian Program publik (halaman login): kota/kab -> kecamatan -> kelurahan -> PTS. Navigasi lewat public/js/drilldown.js --}}
@php
    use App\Models\Kpisampah;
    use Illuminate\Support\Carbon;
    $num = fn ($v) => number_format($v, 0, ',', '.');
    $sel = fn ($k) => Kpisampah::KLASTER[$k]['sel'] ?? '';
    $persen = fn ($p) => Kpisampah::formatPersen($p === null ? null : (float) $p);
    $namaBulan = $params['bulan'] ? Carbon::parse($params['bulan'].'-01')->translatedFormat('F Y') : null;
    $isDefault = ! $params['kecamatan'] && ! $params['desa'] && ! $params['klaster'];
    $persenTotal = $total_keseluruhan->persen_pengurangan ?? null;
    $klasterTotal = Kpisampah::klaster($persenTotal, true);
@endphp
<div data-drilldown-root data-params='@json($params)'>
    <div class="capaian-toolbar d-flex flex-wrap align-items-end justify-content-end gap-2 mb-3">
        <div class="capaian-field">
            <label class="form-label small mb-1" for="capaianBulan">Bulan</label>
            <select name="bulan" id="capaianBulan" class="form-select form-select-sm">
                @forelse ($bulanList as $bulan)
                    <option value="{{ $bulan }}" @selected($bulan === $params['bulan'])>{{ Carbon::parse($bulan.'-01')->translatedFormat('F Y') }}</option>
                @empty
                    <option value="">Belum ada data</option>
                @endforelse
            </select>
        </div>
        <div class="capaian-field">
            <label class="form-label small mb-1" for="capaianKlaster">Klaster</label>
            <select name="klaster" id="capaianKlaster" class="form-select form-select-sm">
                <option value="">Semua Klaster</option>
                @foreach (Kpisampah::KLASTER as $kode => $k)
                    <option value="{{ $kode }}" @selected($kode === $params['klaster'])>{{ $k['label'] }}</option>
                @endforeach
            </select>
        </div>
        @if ($isDefault)
            <button type="button" class="btn btn-sm btn-primary capaian-action" data-png-download
                data-png-lib="{{ asset('assets/vendor/libs/html-to-image/html-to-image.js') }}">
                <i class="ri-download-2-line me-1" aria-hidden="true"></i> Unduh PNG
            </button>
        @else
            <button type="button" class="btn btn-sm btn-outline-secondary capaian-action" data-drill-reset>
                <i class="ri-refresh-line me-1" aria-hidden="true"></i> Reset
            </button>
        @endif
    </div>

    <div data-png-target>
        <div class="mb-3">
            <div class="small text-muted">Capaian Keseluruhan 5 Kota/Kabupaten{{ $namaBulan ? ' ('.$namaBulan.')' : '' }}</div>
            @if ($persenTotal === null)
                <span class="text-muted small">Belum ada data</span>
            @else
                <span class="badge rounded-pill fs-6 {{ Kpisampah::KLASTER[$klasterTotal]['badge'] }}">{{ $persen($persenTotal) }}</span>
            @endif
        </div>

        <p class="small text-muted mb-3">
            <span class="badge bg-success">Hijau</span> &gt; 20%;
            <span class="badge bg-warning">Kuning</span> 10% s.d. &le; 20%;
            <span class="badge bg-danger">Merah</span> &lt; 10%.
            <em class="d-block mt-1">Target KPI terpenuhi atau 100% jika setiap kelurahan/desa &gt; 20%</em>
        </p>

        <div class="table-responsive scroll-box mb-5">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th class="text-center" width="1">No</th>
                        <th class="text-center">Kecamatan</th>
                        <th class="text-center">Persentase Pengurangan Sampah (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kecamatan as $lokasi)
                        <tr class="table-light">
                            <th colspan="2" scope="rowgroup">{{ $lokasi->nama_lokasi }}</th>
                            <th class="text-center {{ $sel($lokasi->klaster) }}">{{ $persen($lokasi->persen) }}</th>
                        </tr>
                        @foreach ($lokasi->kecamatan as $kec)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td @class(['fw-bold' => $kec->id_kecamatan === $params['kecamatan']])>
                                    <a href="#" data-drill='@json(['kecamatan' => $kec->id_kecamatan, 'desa' => null])'>{{ $kec->kecamatan }}</a>
                                </td>
                                <td class="text-center {{ $sel($kec->klaster) }}">{{ $persen($kec->persen) }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">{{ $params['klaster'] ? 'Tidak ada kecamatan pada klaster ini' : 'Belum ada kelompok yang ditempatkan' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($params['kecamatan'])
        <h6 class="mb-2">Sebaran Lokasi (Kelurahan) &mdash; Kecamatan {{ $namaKecamatan }}</h6>
        <div class="table-responsive scroll-box mb-5">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th class="text-center" width="1">No</th>
                        <th class="text-center">Kelurahan/Desa</th>
                        <th class="text-center">Persentase Pengurangan Sampah (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kelurahan as $kel)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td @class(['fw-bold' => $kel->id_desa === $params['desa']])>
                                <a href="#" data-drill='@json(['desa' => $kel->id_desa])'>{{ $kel->desa }}</a>
                            </td>
                            <td class="text-center {{ $sel($kel->klaster) }}">{{ $persen($kel->persen) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">{{ $params['klaster'] ? 'Tidak ada kelurahan/desa pada klaster ini' : 'Belum ada kelurahan/desa' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($params['desa'])
        <h6 class="mb-2">Pelaksana (PTS) di Kelurahan/Desa {{ $namaDesa }}</h6>
        <div class="table-responsive scroll-box">
            <table class="table table-sm table-bordered mb-0 table-pts">
                <thead>
                    <tr>
                        <th class="text-center" width="1">No</th>
                        <th class="text-center">PTS</th>
                        <th class="text-center">Lokasi</th>
                        <th class="text-center">JML. MHS</th>
                        <th class="text-center">JML. DPL</th>
                        <th class="text-center">Ketua Kelompok</th>
                        <th class="text-center">Persentase Pengurangan Sampah (%)</th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kelompok as $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $row->nama_pt }}</td>
                            <td>{{ $row->lokasi }}</td>
                            <td class="text-center">{{ $num($row->jumlah_mahasiswa) }}</td>
                            <td class="text-center">{{ $num($row->jumlah_dpl) }}</td>
                            <td class="text-center">{{ $num($row->jumlah_ketua) }}</td>
                            <td class="text-center {{ $sel($row->klaster_pt) }}">{{ $persen($row->persen_pt) }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-icon btn-outline-primary" data-detail-toggle="detail-{{ $row->kodept }}"
                                    title="Detail" aria-label="Detail {{ $row->nama_pt }}">
                                    <i class="ri-eye-line" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                        <tr id="detail-{{ $row->kodept }}" class="d-none">
                            <td colspan="8" class="bg-lighter">
                                <p class="small mb-2">
                                    <span class="fw-semibold">Ketua Kelompok:</span>
                                    {{ $row->ketua->isNotEmpty() ? $row->ketua->implode(', ') : '-' }}
                                </p>
                                <table class="table table-sm table-bordered mb-0 bg-white">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Permasalahan</th>
                                            <th class="text-center">Solusi</th>
                                            <th class="text-center">Kebutuhan Dukungan</th>
                                            <th class="text-center">Tindak Lanjut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($row->capaian as $isian)
                                            <tr>
                                                <td>{!! nl2br(e($isian->permasalahan)) !!}</td>
                                                <td>{!! nl2br(e($isian->solusi)) !!}</td>
                                                <td>{!! nl2br(e($isian->kendala)) !!}</td>
                                                <td class="text-center">{!! \App\Models\Kpicapaian::statusBadge($isian->status_capaian) !!}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted">Belum mengisi capaian</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">Belum ada PTS di kelurahan/desa ini</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
