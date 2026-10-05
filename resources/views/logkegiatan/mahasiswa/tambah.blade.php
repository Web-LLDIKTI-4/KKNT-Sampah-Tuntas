<form id="form-tambah" method="post" action="{{ url('logkegiatan/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    @include('logkegiatan.mahasiswa._fields')
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
