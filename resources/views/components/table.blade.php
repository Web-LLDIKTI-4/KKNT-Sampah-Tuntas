@props([
    'id' => 'dataTable',
    'tableClass' => 'table table-bordered table-sm',
    'ajax' => null,
    'columns' => [],
    'order' => [[0, 'asc']],
    'searching' => true,
    'lengthChange' => true,
])

<table
    class="{{ $tableClass }}"
    id="{{ $id }}"
    data-datatable
    data-ajax="{{ $ajax }}"
    data-columns='@json($columns)'
    data-order='@json($order)'
    data-searching="{{ $searching ? 'true' : 'false' }}"
    data-length-change="{{ $lengthChange ? 'true' : 'false' }}"
>
    <thead class="text-center">
        {{ $thead ?? '' }}
    </thead>
    <tbody></tbody>
</table>

<script src="{{ asset('js/table-init.js') }}?v={{ filemtime(public_path('js/table-init.js')) }}"></script>