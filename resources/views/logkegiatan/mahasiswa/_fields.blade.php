{{-- Field Log Harian survei rumah tangga; dipakai tambah & edit. $data: Logkegiatan|null --}}
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
    <input type="text" name="nama_kepala_keluarga" class="form-control form-control-sm" required maxlength="150" placeholder="Nama kepala keluarga" value="{{ $nilai('nama_kepala_keluarga') }}">
    <label>Nama Kepala Keluarga {!! $wajib !!}</label>
    <span id="nama_kepala_keluarga_error" class="text-danger"></span>
</div>
<div class="form-group form-floating form-floating-outline mb-6">
    <input type="text" name="alamat_rumah" class="form-control form-control-sm" required maxlength="255" placeholder="Alamat rumah" value="{{ $nilai('alamat_rumah') }}">
    <label>Alamat Rumah {!! $wajib !!}</label>
    <span id="alamat_rumah_error" class="text-danger"></span>
</div>
<div class="row">
    <div class="form-group form-floating form-floating-outline mb-6 col">
        <input type="text" name="rt" class="form-control form-control-sm" required maxlength="5" inputmode="numeric" placeholder="001" value="{{ $nilai('rt') }}">
        <label>RT {!! $wajib !!}</label>
        <span id="rt_error" class="text-danger"></span>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6 col">
        <input type="text" name="rw" class="form-control form-control-sm" required maxlength="5" inputmode="numeric" placeholder="001" value="{{ $nilai('rw') }}">
        <label>RW {!! $wajib !!}</label>
        <span id="rw_error" class="text-danger"></span>
    </div>
</div>

<fieldset class="mb-6">
    <legend class="form-label fs-6 mb-2">Apakah saat ini di rumah sudah menerapkan pemilahan sampah? {!! $wajib !!}</legend>
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
        <input type="number" name="organik_kg" id="log-organik" class="form-control form-control-sm" required min="0" max="99999999" step="0.01" value="{{ $nilai('organik_kg') }}" aria-describedby="log-organik-help">
        <label for="log-organik">Sampah Organik Terkelola (kg) {!! $wajib !!}</label>
    </div>
    <div id="log-organik-help" class="form-text">Dikomposkan (loseda, dikubur langsung ke tanah, bata terawang, komposter, biopori, dll); Maggot BSF; Digunakan sebagai pakan ternak/ikan; Diserahkan ke fasilitas pengolahan sampah (TPS 3R, TPST, POO, dll)</div>
    <span id="organik_kg_error" class="text-danger"></span>
</div>
<div class="mb-6">
    <div class="form-group form-floating form-floating-outline">
        <input type="number" name="anorganik_kg" id="log-anorganik" class="form-control form-control-sm" required min="0" max="99999999" step="0.01" value="{{ $nilai('anorganik_kg') }}" aria-describedby="log-anorganik-help">
        <label for="log-anorganik">Sampah Anorganik Terkelola (kg) {!! $wajib !!}</label>
    </div>
    <div id="log-anorganik-help" class="form-text">Ditabung ke bank sampah; Dijual/Diambil ke pengepul/pemulung; Disedekahkan ke pemulung; Didaur ulang secara mandiri; dan/atau Diserahkan ke fasilitas pengolahan sampah (TPS 3R, TPST, Pusat Daur Ulang, dll)</div>
    <span id="anorganik_kg_error" class="text-danger"></span>
</div>
<div class="mb-6">
    <div class="form-group form-floating form-floating-outline">
        <input type="number" name="residu_kg" id="log-residu" class="form-control form-control-sm" required min="0" max="99999999" step="0.01" value="{{ $nilai('residu_kg') }}" aria-describedby="log-residu-help">
        <label for="log-residu">Sampah Residu (kg) {!! $wajib !!}</label>
    </div>
    <div id="log-residu-help" class="form-text">Diangkut oleh petugas tanpa pemilahan; Diangkut ke TPS; Dibakar secara terbuka; Dibuang ke sungai; Dibuang ke jalan, lahan kosong, kuburan, dll</div>
    <span id="residu_kg_error" class="text-danger"></span>
</div>
