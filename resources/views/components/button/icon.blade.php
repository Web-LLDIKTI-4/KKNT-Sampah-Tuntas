@props(['action' => 'edit', 'href' => null, 'modal' => null, 'type' => 'button'])
{{-- Tombol ikon aksi baris tabel (view/edit/delete); gaya di public/assets/css/action-buttons.css --}}
@php
    $icons = [
        'view' => 'ri-eye-line',
        'edit' => 'ri-edit-box-line',
        'delete' => 'ri-delete-bin-3-line',
    ];
@endphp
<x-button :variant="false" :href="$href" :modal="$modal" :type="$type" :icon="$icons[$action]" {{ $attributes->class('btn-action-' . $action) }} />
