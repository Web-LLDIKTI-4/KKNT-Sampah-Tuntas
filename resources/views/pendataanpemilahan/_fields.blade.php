@php
    $data ??= null;
    $nilai = fn (string $kolom) => $data ? $data->$kolom : '';
    $wajib = '<span class="text-danger">*</span>';
@endphp
<div class="form-group form-floating form-floating-outline mb-6">
    <input type="date" name="tanggal" class="form-control form-control-sm" required max="{{ date('Y-m-d') }}" value="{{ $nilai('tanggal') }}">
    <label>Tanggal {!! $wajib !!}</label>
    <span id="tanggal_error" class="text-danger"></span>
</div>
<h6 class="mb-4">Data Rumah Tangga</h6>
<div class="form-group form-floating form-floating-outline mb-6">
    <input type="text" name="nama_kepala_keluarga" class="form-control form-control-sm" required maxlength="150" value="{{ $nilai('nama_kepala_keluarga') }}">
    <label>Nama Kepala Keluarga {!! $wajib !!}</label>
    <span id="nama_kepala_keluarga_error" class="text-danger"></span>
</div>
<div class="form-group form-floating form-floating-outline mb-6">
    <input type="text" name="alamat_rumah" class="form-control form-control-sm" required maxlength="255" value="{{ $nilai('alamat_rumah') }}">
    <label>Alamat Rumah {!! $wajib !!}</label>
    <span id="alamat_rumah_error" class="text-danger"></span>
</div>
<div class="row">
    <div class="form-group form-floating form-floating-outline mb-6 col">
        <input type="text" name="rt" class="form-control form-control-sm" required maxlength="5" inputmode="numeric" value="{{ $nilai('rt') }}">
        <label>RT {!! $wajib !!}</label>
        <span id="rt_error" class="text-danger"></span>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6 col">
        <input type="text" name="rw" class="form-control form-control-sm" required maxlength="5" inputmode="numeric" value="{{ $nilai('rw') }}">
        <label>RW {!! $wajib !!}</label>
        <span id="rw_error" class="text-danger"></span>
    </div>
</div>
<fieldset class="mb-6">
    <legend class="form-label fs-6 mb-2">Apakah rumah sudah menerapkan pemilahan sampah? {!! $wajib !!}</legend>
    @php($memilah = $data ? (int) $data->memilah : null)
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="memilah" id="memilah-ya" value="1" required @checked($memilah === 1)>
        <label class="form-check-label" for="memilah-ya">Ya</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="memilah" id="memilah-tidak" value="0" @checked($memilah === 0)>
        <label class="form-check-label" for="memilah-tidak">Tidak</label>
    </div>
    <div><span id="memilah_error" class="text-danger"></span></div>
</fieldset>
<h6 class="mb-4">Timbulan Sampah (kg)</h6>
<div class="mb-6">
    <div class="form-group form-floating form-floating-outline">
        <input type="number" name="organik_kg" id="sampah-organik" class="form-control form-control-sm" required min="0" max="99999999" step="0.01" value="{{ $nilai('organik_kg') }}">
        <label for="sampah-organik">Organik Terkelola (kg) {!! $wajib !!}</label>
    </div>
    <span id="organik_kg_error" class="text-danger"></span>
</div>
<div class="mb-6">
    <div class="form-group form-floating form-floating-outline">
        <input type="number" name="anorganik_kg" id="sampah-anorganik" class="form-control form-control-sm" required min="0" max="99999999" step="0.01" value="{{ $nilai('anorganik_kg') }}">
        <label for="sampah-anorganik">Anorganik Terkelola (kg) {!! $wajib !!}</label>
    </div>
    <span id="anorganik_kg_error" class="text-danger"></span>
    <span>Ditabung ke bank sampang Dijual/Diambil ke pengepul/pemulung: Disedekahkan ke pemulung; Didaur ulang secara mandiri, dan/atau Diserahkan ke fasilitas pengolahan sampah (TPS 3R, TPST, Pusat Daur Ulang, Insinerator, Motah, Nawasena, dll.)</span>
</div>
<div class="mb-6">
    <div class="form-group form-floating form-floating-outline">
        <input type="number" name="residu_kg" id="sampah-residu" class="form-control form-control-sm" required min="0" max="99999999" step="0.01" value="{{ $nilai('residu_kg') }}">
        <label for="sampah-residu">Residu (kg) {!! $wajib !!}</label>
    </div>
    <span id="residu_kg_error" class="text-danger"></span>
</div>
