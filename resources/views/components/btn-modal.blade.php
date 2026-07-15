@props(['url', 'title' => null, 'class' => 'btn btn-sm btn-primary modalButton', 'slot' => 'Buka Form'])
<a class="{{ $class }}" href="#modalku" data-bs-toggle="modal" data-src="{{ $url }}"@if($title) title="{{ $title }}"@endif>
    {{ $slot }}
</a>