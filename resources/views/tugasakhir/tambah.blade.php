<form id="form-tambah" method="post" action="{{ url('tugasakhir/insert') }}" data-ajax-form data-reload-url="{{ url('tugasakhir/listdata') }}">
    @csrf
    @method('PUT')    
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="url" name="tautan" class="form-control form-control-sm" required maxlength="2000" placeholder="https://">
        <label>Tautan laporan tugas akhir KKN</label>
        <div class="alert alert-outline-primary alert-dismissible mt-1">(contoh : https://drive.google.com/drive/folders/1q9d9jNrB07_iYV1fRK9ZhExampleXYZ)</div>
    </div>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
