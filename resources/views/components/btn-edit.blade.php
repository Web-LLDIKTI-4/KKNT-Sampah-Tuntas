@props(['url', 'title' => 'Ubah Data', 'icon' => 'ri-edit-box-line', 'class' => 'btn-action-edit modalButton'])
<a href="#modalku" data-bs-toggle="modal" class="{{ $class }}" data-src="{{ $url }}" title="{{ $title }}">
    @if($slot->isEmpty())
        <i class="{{ $icon }}"></i>
    @else
        {{ $slot }}
    @endif
</a>
