<form id="form-ubah" method="post" action="{{ url('kategori-kegiatan/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_kategori" value="{{$data->id_kategori}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="nama_kategori" class="form-control" required maxlength="255" placeholder="Pendataan" value="{{$data->nama_kategori}}">
        <label>Nama Kategori Kegiatan</label>
    </div>
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
