<form id="form-tambah" method="post" action="{{ url('lokasiprogram/insert') }}">
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="nama_lokasi" class="form-control form-control-sm">
        <label>Nama Lokasi</label>
    </div>
    <hr>
    <x-btn-save formId="form-tambah"><i class="tf-icons ri-save-3-fill ri-16px me-1"></i>Simpan</x-btn-save>
</form>
