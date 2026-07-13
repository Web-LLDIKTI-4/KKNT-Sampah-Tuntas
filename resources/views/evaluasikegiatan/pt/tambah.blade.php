<form id="form-tambah" method="post" action="{{ url('ptevaluasikegiatan/insert') }}">
    @csrf
    @method('PUT')

    @foreach($evaluasi as $item)
        <div class="form-group">
            <label>{!! $item['evaluasi']->pertanyaan !!}</label>
            <textarea class="form-control" name="jawaban[{{ $item['evaluasi']->id_evaluasi }}]">{{ $item['jawaban']->jawaban ?? '' }}</textarea>
        </div>
    @endforeach

    <hr>
    <button type="submit" id="btnSubmit_form-tambah" class="btn btn-sm btn-primary">Simpan</button>
</form>