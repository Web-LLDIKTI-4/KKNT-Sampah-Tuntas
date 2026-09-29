@props([
    'urlModal' => null,
    'titleModal' => "Modal Title",
    'urlView' => null,
    'urlEdit' => null,
    'urlDelete' => null,
    'classModal' => 'btn-action-view',
    'classView' => 'btn-action-view',
    'classEdit' => 'btn-action-edit',
    'classDelete' => 'btn-action-delete',
    'idField' => null,
    'idValue' => null,
    'confirm' => 'Anda yakin ingin menghapus data ini?',
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
        <a href="#modalku" data-bs-toggle="modal" class="modalButton {{ $classEdit }}" data-src="{{ $urlEdit }}" title="Ubah Data">
            <i class="ri-edit-box-line fs-4"></i>
        </a>
    @endif 
    
    @if ($urlDelete && empty($idField) && empty($idValue))
        <a href="javascript:void(0)" id="hapus_{{ $urlDelete }}"  class="{{ $classDelete }}">
            <i class="ri-delete-bin-3-line fs-4"></i>
        </a>
    @endif

    @if ($urlDelete && $idField && $idValue)
        <a href="javascript:void(0)" class="btn-delete {{ $classDelete }}" title="Hapus Data"
            data-url="{{ $urlDelete }}" data-id-field="{{ $idField }}" data-id-value="{{ $idValue }}" data-confirm="{{ $confirm }}">
            <i class="ri-delete-bin-3-line fs-4"></i>
        </a>
    @endif
</div>

{{-- Handler .btn-delete dipindah ke table-init.js agar hanya terdaftar sekali,
     mencegah duplikasi saat komponen ini di-render per-baris oleh DataTables. --}}