<form id="form-ubah" method="post" action="{{ url('panduan/update') }}" enctype="multipart/form-data" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_panduan" value="{{ $panduan->id_panduan }}">
    @include('panduan._form', ['panduan' => $panduan])
    <hr>
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
