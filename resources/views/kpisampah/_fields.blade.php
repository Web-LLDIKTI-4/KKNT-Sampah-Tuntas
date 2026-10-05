{{-- Field data sampah bulanan (urutan = kolom Excel D–W); dipakai kpisampah/form & form capaian KPI. $data: Kpisampah|null --}}
@php
    $data ??= null;
    $nilai = fn (string $kolom) => $data ? $data->$kolom : '';
    $persen = fn (?float $v) => \App\Models\Kpisampah::formatPersen($v);
    $kg = fn (string $kolom) => $data ? number_format($data->$kolom, 2, '.', '') : '';
@endphp
<h6 class="mb-4">Kependudukan & Pemilahan</h6>
<div class="row">
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="jml_rw" class="form-control form-control-sm" required min="0" max="1000000" step="1" value="{{ $nilai('jml_rw') }}">
        <label>Jumlah RW</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="jml_penduduk" class="form-control form-control-sm" required min="0" max="100000000" step="1" value="{{ $nilai('jml_penduduk') }}">
        <label>Jumlah Penduduk (Jiwa)</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="jml_rumah" class="form-control form-control-sm" required min="0" max="1000000" step="1" value="{{ $nilai('jml_rumah') }}">
        <label>Jumlah Rumah</label>
    </div>
    <div class="col-md-6 form-floating form-floating-outline mb-6">
        <input type="number" name="jml_rumah_memilah" class="form-control form-control-sm" required min="0" max="{{ $data?->jml_rumah ?? 1000000 }}" step="1" value="{{ $nilai('jml_rumah_memilah') }}">
        <label>Jumlah Rumah yang Memilah</label>
    </div>
    <div class="col-md-6 form-floating form-floating-outline mb-6">
        <input type="text" class="form-control form-control-sm bg-light" data-hitung="persen_ketaatan" value="{{ $persen($data?->persen_ketaatan) }}" readonly tabindex="-1">
        <label>Persentase Ketaatan Pemilah</label>
    </div>
</div>

<h6 class="mb-4">Timbulan Sampah</h6>
<div class="row">
    <div class="col-md-6 mb-6">
        <div class="form-floating form-floating-outline">
            <input type="number" name="timbulan" id="sampah-timbulan" class="form-control form-control-sm" required min="0" max="9999999999" step="0.01" value="{{ $nilai('timbulan') }}" aria-describedby="sampah-timbulan-help">
            <label for="sampah-timbulan">Timbulan Sampah (Kg/Bulan)</label>
        </div>
        <div id="sampah-timbulan-help" class="form-text">Jumlah penduduk × koefisien timbulan × jumlah hari.</div>
    </div>
</div>

<h6 class="mb-4">Pengolahan Sampah Organik</h6>
<div class="row">
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="organik_sumber" class="form-control form-control-sm" required min="0" max="9999999999" step="0.01" value="{{ $nilai('organik_sumber') }}">
        <label>Diolah di Sumber (Kg/Bulan)</label>
    </div>
    <div class="col-md-5 form-floating form-floating-outline mb-6">
        <input type="text" name="organik_metode" class="form-control form-control-sm" maxlength="255" placeholder="maggot, komposting, dsb" value="{{ $nilai('organik_metode') }}">
        <label>Nama Metode Pengolahan (maggot/komposting/dsb)</label>
    </div>
    <div class="col-md-3 form-floating form-floating-outline mb-6">
        <input type="number" name="organik_metode_unit" class="form-control form-control-sm" min="0" max="1000000" step="1" value="{{ $nilai('organik_metode_unit') }}">
        <label>Jumlah Metode (unit)</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="organik_dlh" class="form-control form-control-sm" required min="0" max="9999999999" step="0.01" value="{{ $nilai('organik_dlh') }}">
        <label>Diolah oleh DLH (Kg/Bulan)</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="text" name="organik_dlh_fasilitas" class="form-control form-control-sm" maxlength="255" placeholder="POO, TPST, TPS3R, PDU" value="{{ $nilai('organik_dlh_fasilitas') }}">
        <label>Nama Fasilitas DLH (POO/TPST/TPS3R/PDU)</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="text" name="organik_dlh_lokasi" class="form-control form-control-sm" maxlength="255" placeholder="Lokasi" value="{{ $nilai('organik_dlh_lokasi') }}">
        <label>Lokasi Fasilitas</label>
    </div>
</div>

<h6 class="mb-4">Pengolahan Sampah Anorganik</h6>
<div class="row">
    <div class="col-md-3 form-floating form-floating-outline mb-6">
        <input type="number" name="anorganik_sumber" class="form-control form-control-sm" required min="0" max="9999999999" step="0.01" value="{{ $nilai('anorganik_sumber') }}">
        <label>Diolah di Sumber (Kg/Bulan)</label>
    </div>
    <div class="col-md-3 form-floating form-floating-outline mb-6">
        <input type="text" name="anorganik_metode" class="form-control form-control-sm" maxlength="255" placeholder="Bank Sampah, Pengepul, dsb" value="{{ $nilai('anorganik_metode') }}">
        <label>Nama Metode (Bank Sampah/Pengepul/dsb)</label>
    </div>
    <div class="col-md-3 form-floating form-floating-outline mb-6">
        <input type="text" name="anorganik_metode_lokasi" class="form-control form-control-sm" maxlength="255" placeholder="Lokasi" value="{{ $nilai('anorganik_metode_lokasi') }}">
        <label>Lokasi Metode</label>
    </div>
    <div class="col-md-3 form-floating form-floating-outline mb-6">
        <input type="number" name="anorganik_metode_unit" class="form-control form-control-sm" min="0" max="1000000" step="1" value="{{ $nilai('anorganik_metode_unit') }}">
        <label>Jumlah Metode (unit)</label>
    </div>
</div>

<h6 class="mb-4">Hasil Pengolahan <small class="text-muted fw-normal">(dihitung otomatis)</small></h6>
<div class="row">
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="text" class="form-control form-control-sm bg-light" data-hitung="pengurangan" value="{{ $kg('pengurangan') }}" readonly tabindex="-1">
        <label>Total Pengolahan (Kg/Bulan)</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="text" class="form-control form-control-sm bg-light" data-hitung="belum_terkelola" value="{{ $kg('belum_terkelola') }}" readonly tabindex="-1">
        <label>Sampah Belum Terkelola (Kg/Bulan)</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="text" class="form-control form-control-sm bg-light" data-hitung="persen_pengurangan" value="{{ $persen($data?->persen_pengurangan) }}" readonly tabindex="-1">
        <label>Persentase Penurunan Sampah</label>
    </div>
    <div class="col-12 mb-6 d-none text-danger small" data-hitung="peringatan" role="alert">Total pengolahan melebihi timbulan sampah.</div>
    <div class="col-12 form-floating form-floating-outline mb-6">
        <textarea name="keterangan" class="form-control form-control-sm" style="height: 80px" maxlength="2000" placeholder="Keterangan">{{ $nilai('keterangan') }}</textarea>
        <label>Keterangan</label>
    </div>
</div>
