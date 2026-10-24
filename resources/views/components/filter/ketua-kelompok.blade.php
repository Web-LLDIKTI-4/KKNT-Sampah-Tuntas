@props(['target' => '#dataTable'])

<div class="col-12 col-md-4 col-lg-3 mt-2 mt-md-0">
    <label for="filterKetuaKelompok" class="form-label small mb-1">Ketua Kelompok</label>
    <select id="filterKetuaKelompok" class="form-select form-select-sm" data-no-search
        data-table-filter="ketua_kelompok" data-table-target="{{ $target }}">
        <option value="">Semua</option>
        <option value="1">Ketua</option>
        <option value="0">Bukan Ketua</option>
    </select>
</div>
