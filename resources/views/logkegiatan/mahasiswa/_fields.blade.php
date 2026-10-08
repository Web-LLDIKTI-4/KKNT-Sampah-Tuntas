@php
    $data ??= null;
    $nilai = fn (string $kolom) => $data ? $data->$kolom : '';
@endphp
<div class="form-group form-floating form-floating-outline mb-6">
    <input type="date" name="tanggal" class="form-control form-control-sm" required max="{{ date('Y-m-d') }}" value="{{ $nilai('tanggal') }}">
    <label>Tanggal <span class="text-danger">*</span></label>
    <span id="tanggal_error" class="text-danger"></span>
</div>
<div class="form-group form-floating form-floating-outline mb-6">
    <select name="id_kpi" class="form-select" required>
        <option value="">Pilih Aktivitas</option>
        @foreach($kpi as $item)
            <option value="{{ $item->id_kpi }}" @selected($nilai('id_kpi') === $item->id_kpi)>{{ $item->nama_kpi }}</option>
        @endforeach
    </select>
    <label>Aktivitas <span class="text-danger">*</span></label>
    <span id="id_kpi_error" class="text-danger"></span>
</div>
<div class="form-group form-floating form-floating-outline mb-6">
    <textarea name="deskripsi" class="form-control" rows="5" maxlength="65000" required placeholder="Pendataan sampah didampingi oleh...">{{ $nilai('deskripsi') }}</textarea>
    <label>Deskripsi Kegiatan <span class="text-danger">*</span></label>
    <span id="deskripsi_error" class="text-danger"></span>
</div>
<div class="row">
    <div class="form-group form-floating form-floating-outline mb-6 col">
        <input type="number" name="volume" class="form-control form-control-sm" required min="0" max="1000000" step="any" value="{{ $nilai('volume') }}">
        <label>Volume/Kuantitas Output <span class="text-danger">*</span></label>
        <span id="volume_error" class="text-danger"></span>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6 col">
        <input type="text" name="satuan" class="form-control form-control-sm" required maxlength="255" value="{{ $nilai('satuan') }}">
        <label>Satuan <span class="text-danger">*</span></label>
        <span id="satuan_error" class="text-danger"></span>
    </div>
</div>
<div class="form-group form-floating form-floating-outline mb-6">
    <input type="url" name="tautan" class="form-control form-control-sm" maxlength="255" value="{{ $nilai('tautan') }}">
    <label>Tautan bukti (opsional)</label>
    <span id="tautan_error" class="text-danger"></span>
</div>
