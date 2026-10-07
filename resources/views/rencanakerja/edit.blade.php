<form id="form-ubah" method="post" action="{{ route('rencanakerja.update') }}" enctype="multipart/form-data" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_rencana_kerja" value="{{ $rencanaKerja->id_rencana_kerja }}">
    @include('rencanakerja._form', ['rencanaKerja' => $rencanaKerja])
    <hr>
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
