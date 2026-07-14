<div>{!! $logbulanan->deskripsi !!}</div>
<hr>
<form method="post" id="form-simpan" action="{{ url('admlogbulanan/updatenilai') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id_logbulanan" value="{{$logbulanan->id_logbulanan}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="nilai" class="form-control">
            @if($anilai)
                @foreach($anilai as $value)
                    <option value="{{$value}}" @if($logbulanan->nilai == $value) selected @endif>{{ $value }}</option>
                @endforeach
            @endif
        </select>
        <label>Nilai</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="hasil_verifikasi" class="form-control">{{ $logbulanan->hasil_verifikasi }}</textarea>
        <label>Catatan Hasil Verifikasi</label>
    </div>
    <div>
        <x-btn-save formId="form-simpan">
            Simpan
        </x-btn-save>
    </div>
</form>
