<form id="form-tambah" method="post" action="{{ url('kecamatan/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <x-form.input name="kecamatan" label="Nama Kecamatan" required maxlength="200" />
    <hr>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
