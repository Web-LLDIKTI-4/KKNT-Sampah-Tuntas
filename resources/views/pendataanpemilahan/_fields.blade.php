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
@foreach(['organik' => 'Organik Terkelola', 'anorganik' => 'Anorganik Terkelola', 'residu' => 'Residu'] as $jenis => $label)
    <div class="mb-6">
        <div class="form-group form-floating form-floating-outline">
            <input type="number" name="{{ $jenis }}_kg" id="sampah-{{ $jenis }}" class="form-control form-control-sm" required min="0" max="99999999" step="0.01" value="{{ $nilai($jenis.'_kg') }}">
            <label for="sampah-{{ $jenis }}">{{ $label }} (kg) {!! $wajib !!}</label>
        </div>
        <span id="{{ $jenis }}_kg_error" class="text-danger"></span>
    </div>
@endforeach
