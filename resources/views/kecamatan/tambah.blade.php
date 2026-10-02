<form id="form-tambah" method="post" action="{{ url('kecamatan/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="kecamatan" class="form-control form-control-sm" required maxlength="200">
        <label>Nama Kecamatan</label>
    </div>
    <hr>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
