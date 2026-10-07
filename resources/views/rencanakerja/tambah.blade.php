<form id="form-tambah" method="post" action="{{ route('rencanakerja.insert') }}" enctype="multipart/form-data" data-ajax-form>
    @csrf
    @method('PUT')
    @include('rencanakerja._form', ['rencanaKerja' => null])
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
