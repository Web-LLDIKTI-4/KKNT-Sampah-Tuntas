<form id="form-ubah" method="post" action="{{ url('logkegiatan/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_log" value="{{ $data->id_log }}">
    @include('logkegiatan.mahasiswa._fields', ['data' => $data])
    <x-button.save formId="form-ubah">
        Simpan
    </x-button.save>
</form>
