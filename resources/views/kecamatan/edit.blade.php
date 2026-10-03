<form id="form-ubah" method="post" action="{{ url('kecamatan/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_kecamatan" value="{{$data->id_kecamatan}}">
    <x-form.input name="kecamatan" label="Nama Kecamatan" :value="$data->kecamatan" required maxlength="200" />
    <hr>
    <x-button.save formId="form-ubah">
        Simpan
    </x-button.save>
</form>
    