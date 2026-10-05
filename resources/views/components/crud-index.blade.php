@props(['listUrl', 'addUrl' => null, 'addTitle' => 'Tambah Data', 'modalSize' => 'modal-lg', 'title' => null, 'id' => 'resultcontent'])
{{-- Halaman index CRUD standar: form & hapus ditangani public/js/crud.js --}}
<x-page-header :title="$title" />

<div class="card" data-crud-index>
    @if ($addUrl || isset($actions))
        <div class="card-header d-flex gap-2">
            @if ($addUrl)
                <x-button :modal="$addUrl" :title="$addTitle" icon="ri-add-circle-line">{{ $addTitle }}</x-button>
            @endif
            {{ $actions ?? '' }}
        </div>
    @endif
    <div class="card-body">
        <p id="{{ $id }}">loading data...</p>
    </div>
</div>
<script>
$(function () {
    var $result = $('#' + @json($id));
    var card = $result.closest('[data-crud-index]')[0];
    $('#modalku').on('show.bs.modal', function (e) {
        // Banyak instance di satu halaman: ukuran modal milik card pemicu
        var owner = $(e.relatedTarget).closest('[data-crud-index]')[0];
        if (owner && owner !== card) {
            return;
        }
        $(this).find('.modal-dialog').removeClass('modal-sm modal-lg modal-xl').addClass(@json($modalSize));
    });
    $result.load(@json($listUrl));
});
</script>
