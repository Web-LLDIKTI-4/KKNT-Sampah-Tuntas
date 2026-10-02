<form id="form-tambah" method="post" action="{{ url('ptevaluasikegiatan/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')

    @foreach($evaluasi as $item)
        <div class="form-group">
            <label>{!! \App\Support\HtmlSanitizer::clean($item['evaluasi']->pertanyaan) !!}</label>
            <textarea class="form-control" maxlength="5000" name="jawaban[{{ $item['evaluasi']->id_evaluasi }}]">{{ $item['jawaban']->jawaban ?? '' }}</textarea>
        </div>
        <br />
    @endforeach

    <hr>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>