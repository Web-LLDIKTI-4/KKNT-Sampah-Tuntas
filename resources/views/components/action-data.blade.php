@props([
    'urlModal' => null,
    'titleModal' => "Modal Title",
    'urlView' => null,
    'urlEdit' => null,
    'urlDelete' => null,
    'classModal' => 'btn-action-view',
    'classView' => 'btn-action-view',
    'classEdit' => 'btn-action-edit',
    'classDelete' => 'btn-action-delete'
])

<div class="d-flex gap-2">
    @if ($urlModal)
        <a class="{{ $classModal }}" href="#modalku" data-bs-toggle="modal" data-src="{{ $urlModal }}" title="{{ $titleModal }}">
            <i class="ri-award-line fs-4"></i>
        </a>
    @endif

    @if ($urlView)
        <a href="{{ $urlView }}" class="{{ $classView }}">
            <i class="ri-eye-line fs-4"></i>
        </a>
    @endif

    @if ($urlEdit)
        <a href="#modalku" data-bs-toggle="modal" class="modalButton {{ $classEdit }}" data-src="{{ $urlEdit }}" title="Edit Data">
            <i class="ri-edit-box-line fs-4"></i>
        </a>
    @endif 
    
    @if ($urlDelete)
        <a href="javascript:void(0)" id="hapus_{{ $urlDelete }}"  class="{{ $classDelete }}">
            <i class="ri-delete-bin-3-line fs-4"></i>
        </a>
    @endif
</div>