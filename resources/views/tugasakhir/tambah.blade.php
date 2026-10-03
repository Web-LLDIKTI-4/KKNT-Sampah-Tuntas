<form id="form-tambah" method="post" action="{{ url('tugasakhir/insert') }}" data-ajax-form data-reload-url="{{ url('tugasakhir/listdata') }}">
    @csrf
    @method('PUT')    
    <x-form.input name="tautan" label="Tautan laporan tugas akhir KKN" type="url" required maxlength="2000" placeholder="https://">
        <div class="alert alert-outline-primary alert-dismissible mt-1">(contoh : https://drive.google.com/drive/folders/1q9d9jNrB07_iYV1fRK9ZhEkpimJsXYZ)</div>
    </x-form.input>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
