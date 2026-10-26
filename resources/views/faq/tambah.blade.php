<form id="form-tambah" method="post" action="{{ url('faq/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    @include('faq._form', ['faq' => null])
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
