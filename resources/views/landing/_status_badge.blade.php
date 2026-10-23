{{-- Badge status; jadi tombol pembuka #modalStatusList bila jumlah > 0 --}}
@php
    $isDone = $status === 'sudah';
    $badgeClass = 'badge ' . ($isDone ? 'bg-label-success' : 'bg-label-danger');
    $icon = $isDone ? 'ri-check-line' : 'ri-close-line';
@endphp
@if ((int) $count > 0)
    <button type="button" class="{{ $badgeClass }} status-badge-btn" data-jenis="{{ $jenis }}" data-status="{{ $status }}"
        data-title="{{ $title }} - {{ $label }} ({{ $fmt($count) }})" aria-haspopup="dialog" aria-controls="modalStatusList">
        <i class="{{ $icon }}" aria-hidden="true"></i> {{ $label }} {{ $fmt($count) }}
    </button>
@else
    <span class="{{ $badgeClass }}"><i class="{{ $icon }}" aria-hidden="true"></i> {{ $label }} {{ $fmt($count) }}</span>
@endif
