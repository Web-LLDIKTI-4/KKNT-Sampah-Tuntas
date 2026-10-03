<form id="form-tambah" method="post" action="{{ url('panduan/insert') }}" enctype="multipart/form-data" data-ajax-form>
    @csrf
    @method('PUT')
    @include('panduan._form', ['panduan' => null])
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
