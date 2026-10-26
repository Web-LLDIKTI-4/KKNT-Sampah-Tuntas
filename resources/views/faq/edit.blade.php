<form id="form-ubah" method="post" action="{{ url('faq/update') }}" enctype="multipart/form-data" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id" value="{{ $faq->id }}">
    @include('faq._form', ['faq' => $faq])
    <hr>
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
