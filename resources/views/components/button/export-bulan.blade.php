@props(['url', 'label' => 'Export Semua'])
{{-- Export keseluruhan; bulan opsional (kosong = semua data) --}}
@php($inputId = 'exportBulan' . substr(md5($url), 0, 6))
<form method="GET" action="{{ $url }}" {{ $attributes->class('d-flex flex-wrap align-items-end gap-2') }}>
    <div>
        <label for="{{ $inputId }}" class="form-label small mb-1">Bulan <span class="text-muted">(kosongkan untuk semua data)</span></label>
        <input type="month" name="bulan" id="{{ $inputId }}" class="form-control form-control-sm" max="{{ now()->format('Y-m') }}">
    </div>
    <x-button type="submit" variant="success" :size="false" icon="ri-file-excel-2-line">{{ $label }}</x-button>
</form>
