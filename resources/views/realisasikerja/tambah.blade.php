<form id="form-tambah" method="post" action="{{ route('realisasikerja.insert') }}" enctype="multipart/form-data" data-ajax-form>
    @csrf
    @method('PUT')
    @include('realisasikerja._form', ['realisasiKerja' => null])
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
