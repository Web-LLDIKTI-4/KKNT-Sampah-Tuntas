<form id="form-ubah" method="post" action="{{ url('lokasiprogram/update') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id" value="{{ $data->id }}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="nama_lokasi" class="form-control form-control-sm" value="{{ $data->nama_lokasi }}">
        <label>Nama Lokasi</label>
    </div>
    <hr>
    <x-btn-save formId="form-ubah">
        Simpan
    </x-btn-save>
</form>
