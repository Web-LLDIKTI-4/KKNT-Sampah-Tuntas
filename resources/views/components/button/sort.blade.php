@props(['type' => 'text'])
{{-- Tombol sort header untuk <table data-sortable> (public/js/table-sort.js); type: text|num --}}
<button type="button" class="table-sort" data-sort-type="{{ $type === 'num' ? 'num' : 'text' }}">{{ $slot }}<i class="ri-arrow-up-down-line" aria-hidden="true"></i></button>
