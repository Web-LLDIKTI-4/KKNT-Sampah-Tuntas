@props(['target' => '#dataTable', 'editUrl', 'exportUrl', 'deleteUrl'])
{{-- Toolbar bulk action untuk <x-table selectable>; logika di public/js/bulk-actions.js. Hanya admin. --}}
@if (auth()->user()?->role === 'admin')
    <div {{ $attributes->class('bulk-toolbar d-flex flex-wrap align-items-center gap-2 p-2 mb-3 border rounded mw-100') }}
        data-bulk-toolbar
        data-target="{{ $target }}"
        data-export-url="{{ $exportUrl }}"
        data-delete-url="{{ $deleteUrl }}"
        role="toolbar"
        aria-label="Aksi untuk baris terpilih"
    >
        <span class="badge rounded-pill bg-label-secondary me-1 text-nowrap" data-bulk-count aria-live="polite">0 dipilih</span>
        <x-button variant="outline-primary" icon="ri-edit-box-line" class="modalButton flex-grow-1 flex-md-grow-0 text-truncate" title="Edit Massal"
            data-bulk-action="edit" data-src="{{ $editUrl }}" data-bs-toggle="modal" data-bs-target="#modalku" :disabled="true">Edit massal</x-button>
        <x-button variant="outline-success" class="flex-grow-1 flex-md-grow-0 text-truncate" icon="ri-file-excel-2-line" data-bulk-action="export" :disabled="true">Export terpilih</x-button>
        <x-button variant="outline-danger" class="flex-grow-1 flex-md-grow-0 text-truncate" icon="ri-delete-bin-3-line" data-bulk-action="delete" :disabled="true">Hapus terpilih</x-button>
        <button type="button" class="btn btn-sm btn-link text-muted ms-md-auto px-1" data-bulk-action="clear" hidden>Batalkan pilihan</button>
    </div>
    <div data-bulk-result></div>
    <script src="{{ asset('js/bulk-actions.js') }}?v={{ filemtime(public_path('js/bulk-actions.js')) }}"></script>
@endif
