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
{{-- Kolom aksi baris tabel; slot untuk aksi tambahan. Hapus ditangani handler global .btn-delete di public/js/crud.js --}}
<div class="d-flex justify-content-center gap-2">
    {{ $slot ?? '' }}

    @if ($urlView)
        <x-button.icon action="view" :href="$urlView" :title="$titleView" />
    @endif

    @if ($urlEdit)
        <x-button.icon action="edit" :modal="$urlEdit" :title="$titleEdit" />
    @endif

    @if ($urlDelete && filled($idValue))
        <x-button.icon action="delete" class="btn-delete" :title="$titleDelete"
            :data-url="$urlDelete" :data-id-field="$idField" :data-id-value="$idValue" :data-confirm="$confirm" />
    @endif
</div>
