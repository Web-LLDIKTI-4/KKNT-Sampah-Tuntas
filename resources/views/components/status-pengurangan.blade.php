@props(['persen' => null])
{{-- Persentase pengurangan sampah + badge klaster --}}
@php
    $persen = $persen === null ? null : (float) $persen;
@endphp
@if ($persen === null)
    <span class="text-muted small">Belum ada data</span>
@else
    <span {{ $attributes->class(['badge rounded-pill', \App\Models\PenguranganSampah::KLASTER[\App\Models\PenguranganSampah::klaster($persen)]['badge']]) }}>{{ \App\Models\PenguranganSampah::formatPersen($persen) }}</span>
@endif
