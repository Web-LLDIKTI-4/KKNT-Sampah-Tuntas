<form id="form-ubah" method="post" action="{{ url('desa/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_desa" value="{{$data->id_desa}}">
    <x-form.select name="id_kecamatan" label="Nama Kecamatan" :options="$kecamatan ? $kecamatan->pluck('kecamatan', 'id_kecamatan') : []" :selected="$data->id_kecamatan" :placeholder="false" required />
    <x-form.input name="desa" label="Nama Desa / Kelurahan" :value="$data->desa" required maxlength="200" />
    <hr>
    <x-button.save formId="form-ubah">
        Simpan
    </x-button.save>
</form>

    