<form id="form-ubah" method="post" action="{{ url('kpitarget/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_target" value="{{$data->id_target}}">
    <div class="form-group form-floating form-floating-outline mb-6">        
        <select name="id_kpi" class="form-control form-control-sm" required>
            @if($kpi)
                @foreach($kpi as $item)
                    <option value="{{$item->id_kpi}}" @if($data->id_kpi == $data->id_kpi) selected @endif>{{$item->nama_kpi}}</option>
                @endforeach
            @endif
        </select>
        <label>Nama KPI</label>
    </div>
    <div class="row">
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="kegiatan" class="form-control form-control-sm" required maxlength="200" value="{{$data->kegiatan}}">
            <label>Kegiatan</label>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-6 form-floating form-floating-outline mb-6">
            <input type="number" name="target" class="form-control form-control-sm" required min="0" max="999999" step="any" value="{{ (float) $data->target }}">
            <label>Target</label>
        </div>
        <div class="form-group col-md-6 form-floating form-floating-outline mb-6">
            <input type="text" name="satuan" class="form-control form-control-sm" required maxlength="50" placeholder="%, Kegiatan, % titik pemilahan" value="{{$data->satuan}}">
            <label>Satuan</label>
        </div>
    </div>
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
    