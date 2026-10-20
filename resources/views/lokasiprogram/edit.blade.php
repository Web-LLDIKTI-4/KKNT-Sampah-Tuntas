<form id="form-ubah" method="post" action="{{ url('lokasiprogram/update') }}" enctype="multipart/form-data" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id" value="{{ $data->id }}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="nama_lokasi" class="form-control form-control-sm" required maxlength="255" value="{{ $data->nama_lokasi }}">
        <label>Nama Lokasi</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="number" name="koefisien_timbulan_sampah" class="form-control form-control-sm" step="0.0001" min="0" max="9999.9999" value="{{ $data->koefisien_timbulan_sampah }}">
        <label>Koefisien Timbulan Sampah <small class="text-muted">(opsional)</small></label>
        <span id="koefisien_timbulan_sampah_error" class="text-danger"></span>
    </div>
    <div class="form-group mb-6">
        <label class="form-label">Gambar Lokasi <small class="text-muted">(opsional, maks. 5MB)</small></label>
        @if($data->gambar)
            <div class="mb-2">
                <img src="{{ asset('storage/'.$data->gambar) }}" alt="Gambar lokasi" style="height:80px;border-radius:8px;object-fit:cover;">
                <small class="d-block text-muted mt-1">Unggah gambar baru untuk menggantinya.</small>
            </div>
        @endif
        <input type="file" name="gambar" class="form-control" accept="image/jpg,image/jpeg,image/png,image/webp">
    </div>
    <hr>
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
