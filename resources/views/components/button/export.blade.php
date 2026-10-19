@props(['url', 'label' => 'Export data', 'icon' => 'ri-file-excel-2-line', 'table' => null])
{{-- table: selector DataTable, params tabel ikut ke URL export (datatables-global.js) --}}
<x-button :href="$url" variant="success" :size="false" :icon="$icon" :data-export-table="$table" {{ $attributes }}>{{ $label }}</x-button>
