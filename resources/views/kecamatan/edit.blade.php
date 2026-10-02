<form id="form-ubah" method="post" action="{{ url('kecamatan/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_kecamatan" value="{{$data->id_kecamatan}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="kecamatan" class="form-control form-control-sm" required maxlength="200" value="{{ $data->kecamatan }}">
        <label>Nama Kecamatan</label>
    </div>
    <hr>
    <x-button.save formId="form-ubah">
        Simpan
    </x-button.save>
</form>
    