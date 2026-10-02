@props(['url', 'label' => 'Export data', 'icon' => 'ri-file-excel-2-line'])
<x-button :href="$url" variant="success" :size="false" :icon="$icon" {{ $attributes }}>{{ $label }}</x-button>
