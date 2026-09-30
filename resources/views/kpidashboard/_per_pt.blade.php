@php
    $angka = fn ($v) => \App\Models\Kpicapaian::formatAngka($v);
    $badge = fn ($p) => $p >= 100 ? 'bg-label-success' : ($p >= 50 ? 'bg-label-warning' : 'bg-label-danger');
    $capaian = fn ($p) => $p === null ? '-' : '<span class="badge rounded-pill '.$badge($p).'">'.\App\Models\Kpicapaian::formatPersen($p).'</span>';
    $num = fn ($v) => number_format($v, 0, ',', '.');
    $detail = $detail ?? false;
@endphp

<div class="table-responsive">
    <table class="table table-sm table-bordered mb-0">
        <thead>
            <tr>
                <th>Perguruan Tinggi</th>
                <th>Kegiatan</th>
                @if ($detail)
                    <th class="text-center">Target</th>
                    <th class="text-center">Realisasi</th>
                    <th class="text-center">Selesai</th>
                @endif
                <th class="text-center">Mahasiswa</th>
                <th class="text-center">Kelompok</th>
                <th class="text-center">Capaian Kegiatan</th>
                <th class="text-center">Rata-rata Capaian</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($perPt as $pt)
                @foreach ($pt->kegiatan as $row)
                    <tr>
                        @if ($loop->first)
                            <td rowspan="{{ $loop->count }}" class="align-top">{{ $pt->nama_pt }}</td>
                        @endif
                        <td>
                            {{ $row->kegiatan }}
                            <small class="d-block text-muted">{{ $row->nama_kpi }}@unless ($detail) · target {{ $angka($row->target) }} {{ $row->satuan }}@endunless</small>
                        </td>
                        @if ($detail)
                            <td class="text-center text-nowrap">{{ $angka($row->target) }} {{ $row->satuan }}</td>
                            <td class="text-center text-nowrap">{{ $row->realisasi === null ? '-' : $angka($row->realisasi).' '.$row->satuan }}</td>
                            <td class="text-center">{{ $row->jumlah_selesai }}/{{ $row->jumlah_kelompok }}</td>
                        @endif
                        @if ($loop->first)
                            <td rowspan="{{ $loop->count }}" class="text-center align-top">{{ $num($pt->jumlah_mahasiswa) }}</td>
                            <td rowspan="{{ $loop->count }}" class="text-center align-top">{{ $num($pt->jumlah_kelompok) }}</td>
                        @endif
                        <td class="text-center">{!! $capaian($row->capaian) !!}</td>
                        @if ($loop->first)
                            <td rowspan="{{ $loop->count }}" class="text-center align-top">
                                {!! $capaian($pt->capaian) !!}
                                <small class="d-block text-muted mt-1">{{ $pt->kegiatan_berdata }}/{{ $pt->jumlah_kegiatan }} berdata</small>
                            </td>
                        @endif
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="{{ $detail ? 9 : 6 }}" class="text-center text-muted">Belum ada data capaian</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
