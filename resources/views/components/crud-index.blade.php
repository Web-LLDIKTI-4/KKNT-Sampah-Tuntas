@props(['listUrl', 'addUrl' => null, 'addTitle' => 'Tambah Data', 'modalSize' => 'modal-lg', 'title' => null])
{{-- Halaman index CRUD standar: form & hapus ditangani public/js/crud.js --}}
<x-page-header :title="$title" />

<div class="card">
    @if ($addUrl || isset($actions))
        <div class="card-header d-flex gap-2">
            @if ($addUrl)
                <x-button :modal="$addUrl" :title="$addTitle" icon="ri-add-circle-line">{{ $addTitle }}</x-button>
            @endif
            {{ $actions ?? '' }}
        </div>
    @endif
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
$(function () {
    $('#modalku').on('show.bs.modal', function () {
        $('.modal-dialog').addClass(@json($modalSize));
    });
    $('#resultcontent').load(@json($listUrl));
});
</script>
