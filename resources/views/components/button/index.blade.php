@props([
    'variant' => 'primary',
    'size' => 'sm',
    'icon' => null,
    'href' => null,
    'modal' => null,
    'type' => 'button',
    'disabled' => false,
])
{{-- Tombol dasar: href → link, modal → isi #modalku dari URL (handler .modalButton di layouts/app), selain itu <button> --}}
@php
    $isLink = $href || $modal;
    $attributes = $attributes
        ->class([
            'btn btn-' . $variant => $variant,
            'btn-' . $size => $variant && $size,
            'modalButton' => $modal,
            'disabled' => $disabled && $isLink,
        ])
        ->merge($isLink
            ? [
                'href' => $modal ? '#modalku' : $href,
                'data-bs-toggle' => $modal ? 'modal' : null,
                'data-src' => $modal,
                'aria-disabled' => $disabled ? 'true' : null,
            ]
            : ['type' => $type, 'disabled' => $disabled]);
    $hasLabel = trim((string) $slot) !== '';
@endphp
<{{ $isLink ? 'a' : 'button' }} {{ $attributes }}>@if ($icon)<i class="{{ $icon }}{{ $hasLabel ? ' me-1' : '' }}"></i>@endif{{ $slot }}</{{ $isLink ? 'a' : 'button' }}>
