@props(['target' => '#dataTable'])

<div class="row mb-3">
    <div class="col-12 col-md-4 col-lg-3">
        <label for="filterLokasiStatus" class="form-label small mb-1">Status Lokasi</label>
        <select id="filterLokasiStatus" class="form-select form-select-sm" data-no-search
            data-table-filter="lokasi_status" data-table-target="{{ $target }}">
            <option value="">Semua</option>
            <option value="belum">Belum set</option>
            <option value="sudah">Sudah set</option>
        </select>
    </div>
</div>
