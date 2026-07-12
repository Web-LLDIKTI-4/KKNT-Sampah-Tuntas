<form id="form-ubah" method="post" action="{{ url('kpitarget/update') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id_target" value="{{$data->id_target}}">
    <div class="form-group form-floating form-floating-outline mb-6">        
        <select name="id_kpi" class="form-control form-control-sm">
            @if($kpi)
                @foreach($kpi as $item)
                    <option value="{{$item->id_kpi}}" @if($data->id_kpi == $data->id_kpi) selected @endif>{{$item->nama_kpi}}</option>
                @endforeach
            @endif
        </select>
        <label>Key performance indicator</label>
    </div>
    <div class="row">
        <div class="form-group col-md-4 form-floating form-floating-outline mb-6">
            <input type="text" name="tahapan" class="form-control form-control-sm" value="{{$data->tahapan}}">
            <label>Tahapan</label>
        </div>
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="nama_kpitarget" class="form-control form-control-sm" value="{{$data->nama_kpitarget}}">
            <label>Target Key performance indicator</label>
        </div>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="persen" class="form-control form-control-sm" value="{{$data->persen}}">
        <label>Percen</label>
    </div>
    <hr>
    <button type="submit" id="btnSubmit_form-tambah" class="btn btn-sm btn-primary">Simpan</button>
</form>
    