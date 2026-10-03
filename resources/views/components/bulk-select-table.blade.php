@props([
    'id' => 'bulkTable',
    'formId' => 'form-create',
    'ajax',
    'action',
    // [['data' => 'nim', 'label' => 'Nim', 'className' => '...'], ...] — kolom No & checkbox ditambahkan otomatis
    'columns' => [],
    'ptOptions' => [],
    'lokasiOptions' => [],
    'submitLabel' => 'Simpan',
    // 'page' = muat ulang halaman, atau selector tabel yang di-reload (mis. '#dataTable')
    'afterSuccess' => 'page',
])
@php
    $dtColumns = [['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'className' => 'text-center', 'searchable' => false, 'orderable' => false]];
    foreach ($columns as $col) {
        $dtColumns[] = ['data' => $col['data'], 'name' => $col['data']] + array_intersect_key($col, array_flip(['className', 'searchable', 'orderable']));
    }
    $dtColumns[] = ['data' => 'pilih', 'name' => 'pilih', 'className' => 'text-center', 'searchable' => false, 'orderable' => false];
@endphp
<div class="row g-3 mb-3" data-bulk-filter="{{ $id }}">
    <div class="col-md-6">
        <label class="form-label" for="{{ $id }}_kodept">Perguruan Tinggi</label>
        <select class="form-select form-select-sm" id="{{ $id }}_kodept" data-param="kodept">
            <option value="">— Semua —</option>
            @foreach($ptOptions as $value => $text)
                <option value="{{ $value }}">{{ $text }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $id }}_lokasi">Lokasi Program KKN</label>
        <select class="form-select form-select-sm" id="{{ $id }}_lokasi" data-param="location_program">
            <option value="">— Semua —</option>
            @foreach($lokasiOptions as $value => $text)
                <option value="{{ $value }}">{{ $text }}</option>
            @endforeach
        </select>
    </div>
</div>

<form method="post" id="{{ $formId }}" action="{{ $action }}" data-bulk-form="{{ $id }}" data-after-success="{{ $afterSuccess }}">
    @csrf
    @method('PUT')
    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
        <x-button.save :formId="$formId">{{ $submitLabel }}</x-button.save>
        <span class="badge bg-label-primary" data-bulk-count aria-live="polite">0 dipilih</span>
    </div>
    <p class="small text-muted mb-3">
        Pilih Perguruan Tinggi dan/atau Lokasi untuk menampilkan data. Centang hanya berlaku di halaman yang sedang tampil;
        pindah halaman, mencari, atau mengganti filter akan mengosongkan pilihan.
    </p>
    <div class="table-responsive">
        <table class="table table-bordered table-sm" id="{{ $id }}"
            data-bulk-select
            data-ajax="{{ $ajax }}"
            data-columns='@json($dtColumns)'>
            <thead class="text-center">
                <tr>
                    <th width="1">No</th>
                    @foreach($columns as $col)
                        <th>{{ $col['label'] }}</th>
                    @endforeach
                    <th width="1">
                        <input type="checkbox" class="form-check-input" data-bulk-all aria-label="Pilih semua di halaman ini">
                    </th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</form>

<script src="{{ asset('js/bulk-select.js') }}?v={{ filemtime(public_path('js/bulk-select.js')) }}"></script>
