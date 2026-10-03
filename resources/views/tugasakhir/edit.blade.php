<form id="form-ubah" method="post" action="{{ url('tugasakhir/update') }}" data-ajax-form data-reload-url="{{ url('tugasakhir/listdata') }}">
    @csrf
    @method('PUT')    
    <input type="hidden" name="id_tugasakhir" value="{{$data->id_tugasakhir}}">
    <x-form.input name="tautan" label="Tautan laporan tugas akhir KKN" type="url" :value="$data->tautan" required maxlength="2000">
        <div class="alert alert-outline-primary alert-dismissible mt-1">(contoh : https://drive.google.com/drive/folders/1q9d9jNrB07_iYV1fRK9ZhEkpimJsXYZ)</div>
    </x-form.input>
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
