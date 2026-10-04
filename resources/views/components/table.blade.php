@props([
    'id' => 'dataTable',
    'tableClass' => 'table table-bordered table-sm',
    'ajax' => null,
    'columns' => [],
    'order' => [[0, 'asc']],
    'searching' => true,
    'lengthChange' => true,
    'pageLength' => null,
    'wrap' => [],
    'wrapText' => [],
    'strip' => [],
    'onDraw' => null,
    'scrollX' => false,
    // Teks DataTables tambahan/pengganti default (search, zeroRecords, dst.)
    'language' => [],
    'theadClass' => 'text-center',
    // Mode client-side: tanpa ajax, baris tbody diisi lewat slot
    'clientSide' => false,
])

<table
    class="{{ $tableClass }}"
    id="{{ $id }}"
    data-datatable
    @if($clientSide) data-client-side="true" @endif
    data-ajax="{{ $ajax }}"
    data-columns='@json($columns)'
    data-order='@json($order)'
    data-searching="{{ $searching ? 'true' : 'false' }}"
    data-length-change="{{ $lengthChange ? 'true' : 'false' }}"
    @if($pageLength) data-page-length="{{ (int) $pageLength }}" @endif
    @if($wrap) data-wrap='@json($wrap)' @endif
    @if($wrapText) data-wrap-text='@json($wrapText)' @endif
    @if($strip) data-strip='@json($strip)' @endif
    @if($onDraw) data-on-draw="{{ $onDraw }}" @endif
    @if($scrollX) data-scroll-x="true" @endif
    @if($language) data-language='@json($language)' @endif
>
    <thead @if($theadClass) class="{{ $theadClass }}" @endif>
        {{ $thead ?? '' }}
    </thead>
    <tbody>{{ $clientSide ? $slot : '' }}</tbody>
</table>

<script src="{{ asset('js/table-init.js') }}?v={{ filemtime(public_path('js/table-init.js')) }}"></script>