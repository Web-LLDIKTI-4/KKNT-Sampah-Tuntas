@props(['persen' => null])
{{-- Persentase pengurangan sampah + badge klaster --}}
@php
    $persen = $persen === null ? null : (float) $persen;
@endphp
@if ($persen === null)
    <span class="text-muted small">Belum ada data</span>
@else
    <span {{ $attributes->class(['badge rounded-pill', \App\Models\Kpisampah::KLASTER[\App\Models\Kpisampah::klaster($persen)]['badge']]) }}>{{ \App\Models\Kpisampah::formatPersen($persen) }}</span>
@endif
