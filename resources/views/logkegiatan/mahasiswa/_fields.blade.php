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
    <select name="id_kategori" class="form-select" required>
        <option value="">Pilih Kategori Kegiatan</option>
        @foreach($kategoriKegiatan as $item)
            <option value="{{ $item->id_kategori }}" @selected($nilai('id_kategori') === $item->id_kategori)>{{ $item->nama_kategori }}</option>
        @endforeach
    </select>
    <label>Kategori Kegiatan <span class="text-danger">*</span></label>
    <span id="id_kategori_error" class="text-danger"></span>
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
