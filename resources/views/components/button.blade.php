@props([
    'modal' => null, 
    'modalSrc' => null, 
    'title' => null,
    'disabled' => false
])
<button 
    {{ $attributes->merge(['class' => 'btn' . ($modal ? ' modalButton' : '')]) }} 
    @if($modal) data-bs-toggle="modal" data-bs-target="#{{ $modal }}" @endif
    @if($modalSrc) data-src="{{ $modalSrc }}" @endif
    @if($title) title="{{ $title }}" @endif
    @if($disabled) disabled @endif
>
    {{ $slot }}
</button>
