<form id="form-tambah" method="post" action="{{ url('ptevaluasikegiatan/insert') }}">
    @csrf
    @method('PUT')

    @foreach($evaluasi as $item)
        <div class="form-group">
            <label>{!! $item['evaluasi']->pertanyaan !!}</label>
            <textarea class="form-control" name="jawaban[{{ $item['evaluasi']->id_evaluasi }}]">{{ $item['jawaban']->jawaban ?? '' }}</textarea>
        </div>
        <br />
    @endforeach

    <hr>
    <x-btn-save formId="form-tambah">
        Simpan
    </x-btn-save>
</form>