@props(['persen' => null])
{{-- Persentase pengurangan sampah + status & capaian KPI (terpenuhi bila >= Kpisampah::TARGET_PENGURANGAN) --}}
@php
    $persen = $persen === null ? null : (float) $persen;
    $terpenuhi = \App\Models\Kpisampah::terpenuhi($persen);
@endphp
@if ($persen === null)
    <span class="text-muted small">Belum ada data</span>
@else
    <span {{ $attributes->class(['badge rounded-pill', \App\Models\Kpisampah::KLASTER[\App\Models\Kpisampah::klaster($persen)]['badge']]) }}>{{ \App\Models\Kpisampah::formatPersen($persen) }}</span>
    <small class="d-block mt-1 {{ $terpenuhi ? 'text-success' : 'text-danger' }}">{{ $terpenuhi ? 'Target terpenuhi' : 'Belum terpenuhi' }} · Capaian {{ \App\Models\Kpisampah::formatPersen(\App\Models\Kpisampah::capaian($persen)) }}</small>
@endif
