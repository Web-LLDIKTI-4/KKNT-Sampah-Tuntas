{{-- Isi modal detail capaian PTS di satu kelurahan; kolom sama dengan detail PTS laporan capaian publik --}}
@php
    use App\Models\CapaianKegiatan;
    use Illuminate\Support\Carbon;
    $teks = fn ($v) => $v ? nl2br(e($v)) : '-';
@endphp
@if ($detail->isEmpty())
    <p class="text-muted mb-0">Belum ada ketua kelompok PTS ini di kelurahan/desa tersebut.</p>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-sm align-top mb-0">
            <thead>
                <tr>
                    @if ($semuaBulan)
                        <th class="text-center">Bulan</th>
                    @endif
                    <th class="text-center">Permasalahan</th>
                    <th class="text-center">Solusi</th>
                    <th class="text-center">Kebutuhan Dukungan</th>
                    <th class="text-center">Tindak Lanjut</th>
                    <th class="text-center">Ketua Kelompok</th>
                    <th class="text-center">No. Kontak</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($detail as $isian)
                    <tr>
                        @if ($semuaBulan)
                            <td class="text-nowrap">{{ $isian->bulan ? Carbon::parse($isian->bulan)->translatedFormat('F Y') : '-' }}</td>
                        @endif
                        {{-- Baris tanpa bulan = ketua yang belum mengisi capaian --}}
                        @if ($isian->bulan === null)
                            <td colspan="4" class="text-center text-muted">Belum mengisi capaian</td>
                        @else
                            <td>{!! $teks($isian->permasalahan) !!}</td>
                            <td>{!! $teks($isian->solusi) !!}</td>
                            <td>{!! $teks($isian->kendala) !!}</td>
                            <td class="text-center">{!! $isian->status_capaian ? CapaianKegiatan::statusBadge($isian->status_capaian) : '-' !!}</td>
                        @endif
                        <td>{{ $isian->nama_ketua ?: '-' }}</td>
                        <td class="text-nowrap">{{ $isian->phone ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
