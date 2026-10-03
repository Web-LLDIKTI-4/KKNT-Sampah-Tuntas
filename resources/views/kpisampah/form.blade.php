@php
    $formId = $data ? 'form-ubah' : 'form-tambah';
    $nilai = fn (string $kolom) => $data ? $data->$kolom : '';
    $persen = fn (?float $v) => \App\Models\Kpisampah::formatPersen($v);
@endphp
<form id="{{ $formId }}" method="post" action="{{ url($data ? 'kpisampah/update' : 'kpisampah/insert') }}" data-ajax-form data-sampah-form>
    @csrf
    @method('PUT')
    @if ($data)
        <input type="hidden" name="id_sampah" value="{{ $data->id_sampah }}">
    @endif

    @unless ($desa)
        <div class="alert alert-warning">Anda belum memilih kelurahan pada profil atau belum terdaftar sebagai ketua kelompok.</div>
    @endunless

    <div class="row">
        <x-form.input name="bulan" label="Bulan" type="month" :value="$data?->bulan->format('Y-m') ?? now()->format('Y-m')" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" required min="2020-01" max="{{ now()->format('Y-m') }}" />
        <x-form.input label="Kecamatan" :value="$desa?->kecamatan?->kecamatan ?? '-'" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" readonly tabindex="-1" />
        <x-form.input label="Kelurahan / Desa" :value="$desa?->desa ?? '-'" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" readonly tabindex="-1" />
    </div>

    <h6 class="mb-4">Pemilahan</h6>
    <div class="row">
        <x-form.input name="jml_rw_kbs" label="Jumlah RW KBS Dampingan DLH" type="number" :value="$nilai('jml_rw_kbs')" wrapper-class="col-md-6 form-floating form-floating-outline mb-6" required min="0" max="1000000" step="1" />
        <x-form.input name="jml_rw_non_kbs" label="Jumlah RW Non-KBS" type="number" :value="$nilai('jml_rw_non_kbs')" wrapper-class="col-md-6 form-floating form-floating-outline mb-6" required min="0" max="1000000" step="1" />
        <x-form.input name="jml_rumah" label="Jumlah Rumah Keseluruhan" type="number" :value="$nilai('jml_rumah')" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" required min="0" max="1000000" step="1" />
        <x-form.input name="jml_rumah_memilah" label="Jumlah Rumah yang Memilah" type="number" :value="$nilai('jml_rumah_memilah')" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" required min="0" max="{{ $data?->jml_rumah ?? 1000000 }}" step="1" />
        <x-form.input label="Persentase Ketaatan Pemilah" :value="$persen($data?->persen_ketaatan)" input-class="form-control form-control-sm bg-light" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" data-hitung="persen_ketaatan" readonly tabindex="-1" />
    </div>

    <h6 class="mb-4">Timbulan & Pengurangan Sampah (kg)</h6>
    <div class="row">
        <x-form.input name="timbulan" label="Jumlah Timbulan Sampah (kg/bulan)" type="number" :value="$nilai('timbulan')" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" required min="0" max="9999999999" step="0.01" />
        <x-form.input name="pengurangan_organik" label="Pengurangan Organik" type="number" :value="$nilai('pengurangan_organik')" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" required min="0" max="9999999999" step="0.01" />
        <x-form.input name="pengurangan_anorganik" label="Pengurangan Anorganik" type="number" :value="$nilai('pengurangan_anorganik')" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" required min="0" max="9999999999" step="0.01" />
        <x-form.input label="Pengurangan (Organik + Anorganik)" :value="$data ? number_format($data->pengurangan, 2, '.', '') : ''" input-class="form-control form-control-sm bg-light" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" data-hitung="pengurangan" readonly tabindex="-1" />
        <x-form.input name="residu" label="Residu (kg)" type="number" :value="$nilai('residu')" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" required min="0" max="9999999999" step="0.01" />
        <x-form.input label="Persentase Pengurangan Sampah" :value="$persen($data?->persen_pengurangan)" input-class="form-control form-control-sm bg-light" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" data-hitung="persen_pengurangan" readonly tabindex="-1" />
        <x-form.input name="jml_bank_sampah" label="Jumlah Bank Sampah" type="number" :value="$nilai('jml_bank_sampah')" wrapper-class="col-md-4 form-floating form-floating-outline mb-6" required min="0" max="1000000" step="1" />
    </div>
    <hr>
    <x-button.save :formId="$formId">Simpan</x-button.save>
</form>
