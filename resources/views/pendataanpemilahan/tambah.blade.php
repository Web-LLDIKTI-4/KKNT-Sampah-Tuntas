<form id="form-tambah-pendataan" method="post" action="{{ url('pendataanpemilahan/insert') }}" data-ajax-form data-reload-page>
    @csrf
    @method('PUT')
    @include('pendataanpemilahan._fields')
    <x-button.save formId="form-tambah-pendataan">Simpan</x-button.save>
</form>
