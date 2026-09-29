@php
    $badge = fn ($p) => $p >= 100 ? 'bg-label-success' : ($p >= 50 ? 'bg-label-warning' : 'bg-label-danger');
@endphp

<div class="card-header">
    <h5 class="mb-1">Capaian per KPI</h5>
    <p class="mb-0 card-subtitle">Rata-rata capaian kegiatan dalam KPI; kegiatan tanpa isian selesai tidak dihitung</p>
</div>
<div class="card-body">
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead>
                <tr>
                    <th>KPI</th>
                    <th class="text-center">Kegiatan Berdata</th>
                    <th class="text-center">Rata-rata Capaian</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($perKpi as $row)
                    <tr>
                        <td>{{ $row->nama_kpi }}</td>
                        <td class="text-center">{{ $row->kegiatan_berdata }}/{{ $row->jumlah_kegiatan }}</td>
                        <td class="text-center">
                            @if ($row->capaian === null)
                                -
                            @else
                                <span class="badge rounded-pill {{ $badge($row->capaian) }}">{{ \App\Models\Kpicapaian::formatPersen($row->capaian) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted">Belum ada KPI</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
