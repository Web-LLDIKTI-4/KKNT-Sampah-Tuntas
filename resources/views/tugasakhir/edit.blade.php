<form id="form-ubah" method="post" action="{{ url('tugasakhir/update') }}" data-ajax-form data-reload-url="{{ url('tugasakhir/listdata') }}">
    @csrf
    @method('PUT')    
    <input type="hidden" name="id_tugasakhir" value="{{$data->id_tugasakhir}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="url" name="tautan" class="form-control form-control-sm" required maxlength="2000" value="{{ $data->tautan }}">
        <label>Tautan laporan tugas akhir KKN</label>
        <div class="alert alert-outline-primary alert-dismissible mt-1">(contoh : https://drive.google.com/drive/folders/1q9d9jNrB07_iYV1fRK9ZhEkpimJsXYZ)</div>
    </div>
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
