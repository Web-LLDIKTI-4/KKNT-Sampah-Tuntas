{{-- Laporan berjenjang kecamatan -> kelurahan -> kelompok. Navigasi lewat public/js/drilldown.js --}}
@php
    use App\Models\PenguranganSampah;
    use Illuminate\Support\Carbon;
    $num = fn ($v) => number_format($v, 0, ',', '.');
    $sel = fn ($p) => PenguranganSampah::warnaSel($p);
    $persen = fn ($p) => PenguranganSampah::formatPersen($p === null ? null : (float) $p);
    $namaBulan = $params['bulan'] ? Carbon::parse($params['bulan'].'-01')->translatedFormat('F Y') : null;
    $barisKecamatan = $kecamatan->max(fn ($l) => $l->kecamatan->count()) ?? 0;
    $target = (int) PenguranganSampah::TARGET_PENGURANGAN;
@endphp
<div data-drilldown-root data-params='@json($params)'>
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <div class="small text-muted">Persentase Pengurangan Sampah {{ $namaKecamatan ? 'Kecamatan '.$namaKecamatan : 'Semua Kecamatan' }}{{ $namaBulan ? ' ('.$namaBulan.')' : '' }}</div>
            <x-status-pengurangan :persen="$total->persen_pengurangan" class="fs-6" />
        </div>
        <div style="min-width: 200px">
            <label class="form-label small mb-1">Bulan</label>
            <select name="bulan" class="form-select form-select-sm" aria-label="Pilih bulan">
                @forelse ($bulanList as $bulan)
                    <option value="{{ $bulan }}" @selected($bulan === $params['bulan'])>{{ Carbon::parse($bulan.'-01')->translatedFormat('F Y') }}</option>
                @empty
                    <option value="">Belum ada data</option>
                @endforelse
            </select>
        </div>
    </div>
    <p class="small text-muted mb-3">
        @foreach (PenguranganSampah::KLASTER as $k)
            <span class="badge {{ $k['badge'] }}">{{ $k['label'] }}</span> {{ $k['ket'] }}{{ $loop->last ? '.' : ';' }}
        @endforeach
        Target Pengurangan Sampah: &ge; {{ $target }}%.
    </p>

    <h6 class="mb-2">Sebaran Lokasi (Kecamatan)</h6>
    <div class="table-responsive scroll-box mb-5">
        <table class="table table-sm table-bordered mb-0 text-nowrap">
            <thead>
                <tr>
                    @forelse ($kecamatan as $lokasi)
                        <th>{{ $lokasi->nama_lokasi }}</th>
                        <th class="text-center">Penurunan Timbulan Sampah (%)</th>
                    @empty
                        <th>Kecamatan</th>
                    @endforelse
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < $barisKecamatan; $i++)
                    <tr>
                        @foreach ($kecamatan as $lokasi)
                            @php($kec = $lokasi->kecamatan[$i] ?? null)
                            @if ($kec)
                                <td @class(['fw-bold' => $kec->id_kecamatan === $params['kecamatan']])>
                                    <a href="#" data-drill='@json(['kecamatan' => $kec->id_kecamatan, 'desa' => null])'>{{ $kec->kecamatan }}</a>
                                </td>
                                <td class="text-center {{ $sel($kec->persen) }}">{{ $persen($kec->persen) }}</td>
                            @else
                                <td></td><td></td>
                            @endif
                        @endforeach
                    </tr>
                @endfor
                @if ($barisKecamatan === 0)
                    <tr><td class="text-center text-muted">Belum ada kelompok yang ditempatkan</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    @if ($params['kecamatan'])
        <h6 class="mb-2">Sebaran Lokasi (Kelurahan/Desa) - Kecamatan {{ $namaKecamatan }}</h6>
        <div class="table-responsive scroll-box mb-5">
            <table class="table table-sm table-bordered mb-0" style="max-width: 560px" data-sortable>
                <thead>
                    <tr>
                        <th class="text-center" width="1">No</th>
                        <th><x-button.sort>Kelurahan/Desa</x-button.sort></th>
                        <th class="text-center"><x-button.sort type="num">Penurunan Timbulan Sampah (%)</x-button.sort></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kelurahan as $kel)
                        <tr>
                            <td class="text-center" data-sort-no>{{ $loop->iteration }}</td>
                            <td @class(['fw-bold' => $kel->id_desa === $params['desa']])>
                                <a href="#" data-drill='@json(['desa' => $kel->id_desa])'>{{ $kel->desa }}</a>
                            </td>
                            <td class="text-center {{ $sel($kel->persen) }}" data-sort-value="{{ $kel->persen }}">{{ $persen($kel->persen) }}</td>
                        </tr>
                    @empty
                        <tr data-sort-fixed><td colspan="3" class="text-center text-muted">Belum ada kelurahan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($params['desa'])
        <h6 class="mb-2">PTS / Kelompok di Kelurahan {{ $namaDesa }}</h6>
        <div class="table-responsive scroll-box">
            <table class="table table-sm table-bordered mb-0" data-sortable>
                <thead>
                    <tr>
                        <th class="text-center" width="1">No</th>
                        <th><x-button.sort>PTS</x-button.sort></th>
                        <th><x-button.sort>Lokasi</x-button.sort></th>
                        <th class="text-center"><x-button.sort type="num">Jml Mhs</x-button.sort></th>
                        <th class="text-center"><x-button.sort type="num">DPL</x-button.sort></th>
                        <th class="text-center"><x-button.sort type="num">Ketua Kelompok</x-button.sort></th>
                        <th class="text-center"><x-button.sort type="num">Penurunan Timbulan Sampah (%)</x-button.sort></th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kelompok as $row)
                        <tr>
                            <td class="text-center" data-sort-no>{{ $loop->iteration }}</td>
                            <td>{{ $row->nama_pt }}</td>
                            <td>{{ $row->lokasi }}</td>
                            <td class="text-center" data-sort-value="{{ (int) $row->jumlah_mahasiswa }}">{{ $num($row->jumlah_mahasiswa) }}</td>
                            <td class="text-center" data-sort-value="{{ (int) $row->jumlah_dpl }}">{{ $num($row->jumlah_dpl) }}</td>
                            <td class="text-center" data-sort-value="{{ (int) $row->jumlah_ketua }}">{{ $num($row->jumlah_ketua) }}</td>
                            <td class="text-center {{ $sel($row->persen) }}" data-sort-value="{{ $row->persen }}">
                                {{ $persen($row->persen) }}
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-detail-toggle="detail-{{ $row->kodept }}">Detail</button>
                            </td>
                        </tr>
                        <tr id="detail-{{ $row->kodept }}" class="d-none" data-sort-follow>
                            <td colspan="8" class="bg-lighter">
                                <table class="table table-sm table-bordered mb-0 bg-white">
                                    <thead>
                                        <tr>
                                            <th>Permasalahan</th>
                                            <th>Solusi</th>
                                            <th>Kebutuhan Dukungan</th>
                                            <th class="text-center">Tindak Lanjut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($row->capaian as $isian)
                                            <tr>
                                                <td>{!! nl2br(e($isian->permasalahan)) !!}</td>
                                                <td>{!! nl2br(e($isian->solusi)) !!}</td>
                                                <td>{!! nl2br(e($isian->kendala)) !!}</td>
                                                <td class="text-center">{!! \App\Models\CapaianKegiatan::statusBadge($isian->status_capaian) !!}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted">Belum mengisi capaian</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @empty
                        <tr data-sort-fixed><td colspan="8" class="text-center text-muted">Belum ada kelompok di kelurahan ini</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
