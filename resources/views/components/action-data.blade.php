@props([
    'urlView' => null,
    'urlEdit' => null,
    'urlDelete' => null,
    'idField' => 'id',
    'idValue' => null,
    'confirm' => 'Anda yakin ingin menghapus data ini?',
    'titleView' => 'Lihat Data',
    'titleEdit' => 'Ubah Data',
    'titleDelete' => 'Hapus Data',
])
{{-- Hapus ditangani handler global .btn-delete di public/js/crud.js --}}
<div class="d-flex justify-content-center gap-2">
    @if ($urlView)
        <a href="{{ $urlView }}" class="btn-action-view" title="{{ $titleView }}">
            <i class="ri-eye-line"></i>
        </a>
    @endif

    @if ($urlEdit)
        <a href="#modalku" data-bs-toggle="modal" class="modalButton btn-action-edit" data-src="{{ $urlEdit }}" title="{{ $titleEdit }}">
            <i class="ri-edit-box-line"></i>
        </a>
    @endif

    @if ($urlDelete && filled($idValue))
        <a href="javascript:void(0)" class="btn-delete btn-action-delete" title="{{ $titleDelete }}"
            data-url="{{ $urlDelete }}" data-id-field="{{ $idField }}" data-id-value="{{ $idValue }}" data-confirm="{{ $confirm }}">
            <i class="ri-delete-bin-3-line"></i>
        </a>
    @endif
</div>
