<form id="form-ubah-pendataan" method="post" action="{{ url('pendataanpemilahan/update') }}" data-ajax-form data-reload-page>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_pendataan" value="{{ $data->id_pendataan }}">
    @include('pendataanpemilahan._fields', ['data' => $data])
    <x-button.save formId="form-ubah-pendataan">Simpan</x-button.save>
</form>
