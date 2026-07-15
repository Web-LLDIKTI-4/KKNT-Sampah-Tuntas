@props(['id' => 'dataTable', 'tableClass' => 'table table-bordered table-sm'])

<table class="{{ $tableClass }}" id="{{ $id }}">
    <thead>
        {{ $thead ?? '' }}
    </thead>
    <tbody>
    </tbody>
</table>
