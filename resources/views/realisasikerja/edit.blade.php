<form id="form-ubah" method="post" action="{{ route('realisasikerja.update') }}" enctype="multipart/form-data" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_realisasi_kerja" value="{{ $realisasiKerja->id_realisasi_kerja }}">
    @include('realisasikerja._form', ['realisasiKerja' => $realisasiKerja])
    <hr>
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
