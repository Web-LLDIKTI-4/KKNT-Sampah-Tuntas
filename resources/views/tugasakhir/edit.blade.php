<form id="form-ubah" method="post" action="{{ url('tugasakhir/update') }}">
    @csrf
    @method('PUT')    
    <input type="hidden" name="id_tugasakhir" value="{{$data->id_tugasakhir}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="tautan" class="form-control form-control-sm">{{$data->tautan}}</textarea>
        <label>Tautan laporan tugas akhir KKN</label>
        <div class="alert alert-outline-primary alert-dismissible mt-1">(contoh : https://drive.google.com/drive/folders/1q9d9jNrB07_iYV1fRK9ZhEkpimJsXYZ)</div>
    </div>
    <button type="submit" id="btnSubmit_form-ubah" class="btn btn-sm btn-primary">Simpan</button>
</form>
