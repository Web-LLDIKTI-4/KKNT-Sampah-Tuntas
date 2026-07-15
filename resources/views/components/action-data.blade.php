@props([
    'urlEdit' => null,
    'urlDelete' => null,
    'classEdit' => 'btn-action-edit',
    'classDelete' => 'btn-action-delete'
])

<div class="d-flex gap-2">
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