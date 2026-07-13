<form id="form-tambah" method="post" action="{{ url('tugasakhir/insert') }}">
    @csrf
    @method('PUT')    
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="tautan" class="form-control form-control-sm"></textarea>
        <label>Tautan laporan tugas akhir KKN</label>
        <div class="alert alert-outline-primary alert-dismissible mt-1">(contoh : https://drive.google.com/drive/folders/1q9d9jNrB07_iYV1fRK9ZhEkpimJsXYZ)</div>
    </div>
    <x-btn-save formId="form-tambah">Simpan</x-btn-save>
</form>
