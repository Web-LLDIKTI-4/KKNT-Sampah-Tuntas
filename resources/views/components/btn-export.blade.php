@props(['url', 'label' => 'Export data', 'icon' => 'ri-file-excel-2-line'])
<a href="{{ $url }}" class="btn btn-success">
    <i class="{{ $icon }} me-2"></i> {{ $label }}
</a>
