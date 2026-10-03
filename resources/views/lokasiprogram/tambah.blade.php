<form id="form-tambah" method="post" action="{{ url('lokasiprogram/insert') }}" enctype="multipart/form-data" data-ajax-form>
    @csrf
    @method('PUT')
    <x-form.input name="nama_lokasi" label="Nama Lokasi" required maxlength="255" />
    <div class="form-group mb-6">
        <label class="form-label">Gambar Lokasi <small class="text-muted">(opsional, maks. 5MB)</small></label>
        <input type="file" name="gambar" class="form-control" accept="image/jpg,image/jpeg,image/png,image/webp">
    </div>
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
