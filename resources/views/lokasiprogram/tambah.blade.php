<form id="form-tambah" method="post" action="{{ url('lokasiprogram/insert') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="nama_lokasi" class="form-control form-control-sm">
        <label>Nama Lokasi</label>
    </div>
    <div class="form-group mb-6">
        <label class="form-label">Gambar Lokasi <small class="text-muted">(opsional, maks. 5MB)</small></label>
        <input type="file" name="gambar" class="form-control" accept="image/jpg,image/jpeg,image/png,image/webp">
    </div>
    <hr>
    <x-btn-save formId="form-tambah">Simpan</x-btn-save>
</form>
