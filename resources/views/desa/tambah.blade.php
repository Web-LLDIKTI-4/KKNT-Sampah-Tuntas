<form id="form-tambah" method="post" action="{{ url('desa/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <x-form.select name="id_kecamatan" label="Nama Kecamatan" :options="$kecamatan ? $kecamatan->pluck('kecamatan', 'id_kecamatan') : []" required />
    <x-form.input name="desa" label="Nama Desa / Kelurahan" required maxlength="200" />
    <hr>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>