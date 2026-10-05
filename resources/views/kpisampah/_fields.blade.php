{{-- Field data sampah bulanan; dipakai kpisampah/form & form capaian KPI. $data: Kpisampah|null --}}
@php
    $data ??= null;
    $nilai = fn (string $kolom) => $data ? $data->$kolom : '';
    $persen = fn (?float $v) => \App\Models\Kpisampah::formatPersen($v);
@endphp
<h6 class="mb-4">Pemilahan</h6>
<div class="row">
    <div class="col-md-6 form-floating form-floating-outline mb-6">
        <input type="number" name="jml_rw_kbs" class="form-control form-control-sm" required min="0" max="1000000" step="1" value="{{ $nilai('jml_rw_kbs') }}">
        <label>Jumlah RW KBS Dampingan DLH</label>
    </div>
    <div class="col-md-6 form-floating form-floating-outline mb-6">
        <input type="number" name="jml_rw_non_kbs" class="form-control form-control-sm" required min="0" max="1000000" step="1" value="{{ $nilai('jml_rw_non_kbs') }}">
        <label>Jumlah RW Non-KBS</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="jml_rumah" class="form-control form-control-sm" required min="0" max="1000000" step="1" value="{{ $nilai('jml_rumah') }}">
        <label>Jumlah Rumah Keseluruhan</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="jml_rumah_memilah" class="form-control form-control-sm" required min="0" max="{{ $data?->jml_rumah ?? 1000000 }}" step="1" value="{{ $nilai('jml_rumah_memilah') }}">
        <label>Jumlah Rumah yang Memilah</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="text" class="form-control form-control-sm bg-light" data-hitung="persen_ketaatan" value="{{ $persen($data?->persen_ketaatan) }}" readonly tabindex="-1">
        <label>Persentase Ketaatan Pemilah</label>
    </div>
</div>

<h6 class="mb-4">Timbulan & Pengurangan Sampah (kg)</h6>
<div class="row">
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="timbulan" class="form-control form-control-sm" required min="0" max="9999999999" step="0.01" value="{{ $nilai('timbulan') }}">
        <label>Jumlah Timbulan Sampah (kg/bulan)</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="pengurangan_organik" class="form-control form-control-sm" required min="0" max="9999999999" step="0.01" value="{{ $nilai('pengurangan_organik') }}">
        <label>Pengurangan Organik</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="pengurangan_anorganik" class="form-control form-control-sm" required min="0" max="9999999999" step="0.01" value="{{ $nilai('pengurangan_anorganik') }}">
        <label>Pengurangan Anorganik</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="text" class="form-control form-control-sm bg-light" data-hitung="pengurangan" value="{{ $data ? number_format($data->pengurangan, 2, '.', '') : '' }}" readonly tabindex="-1">
        <label>Pengurangan (Organik + Anorganik)</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="residu" class="form-control form-control-sm" required min="0" max="9999999999" step="0.01" value="{{ $nilai('residu') }}">
        <label>Residu (kg)</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="text" class="form-control form-control-sm bg-light" data-hitung="persen_pengurangan" value="{{ $persen($data?->persen_pengurangan) }}" readonly tabindex="-1">
        <label>Persentase Pengurangan Sampah</label>
    </div>
    <div class="col-md-4 form-floating form-floating-outline mb-6">
        <input type="number" name="jml_bank_sampah" class="form-control form-control-sm" required min="0" max="1000000" step="1" value="{{ $nilai('jml_bank_sampah') }}">
        <label>Jumlah Bank Sampah</label>
    </div>
</div>
