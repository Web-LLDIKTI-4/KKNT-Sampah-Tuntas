<form id="form-ubah" method="post" action="{{ url('kpicapaian/update') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id_capaian" value="{{$data->id_capaian}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kpi" class="form-control form-control-sm">
            @if($kpi)
                @foreach($kpi as $item)
                    <option value="{{$item->id_kpi}}" @if($data->id_kpi == $item->id_kpi) selected @endif>{{$item->nama_kpi}}</option>
                @endforeach
            @endif
        </select>
        <label>Nama KPI</label>
    </div>
    <div id="resulttargetkpi">
        <div class="form-group form-floating form-floating-outline mb-6">
            <select name="id_target" class="form-control form-control-sm">
                @if($kpitarget)
                    @foreach($kpitarget as $item)
                        <option value="{{$item->id_target}}" @if($data->id_target == $item->id_target) selected @endif>{{$item->tahapan}} | {{$item->nama_kpitarget}} ({{$item->persen}} Persen)</option>
                    @endforeach
                @endif
            </select>
            <label>Target KPI</label>
        </div>
    </div>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="status_capaian" class="form-control form-control-sm">
            @foreach(array('Y','N') as $item)
                <option value="{{$item}}" @if($data->status_capaian == $item) selected @endif>{{$item}}</option>
            @endforeach
        </select>
        <label>Capaian</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="permasalahan" class="form-control">{{ $data->permasalahan }}</textarea>
        <label>Permasalahan</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="solusi" class="form-control">{{ $data->solusi }}</textarea>
        <label>Solusi</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="kendala" class="form-control">{{ $data->kendala }}</textarea>
        <label>Kendala</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="tautan" value="{{ $data->tautan }}" class="form-control form-control-sm">
        <label>Tautan</label>
    </div>
    <hr>
    <x-btn-save formId="form-tambah">Simpan</x-btn-save>
</form>
    