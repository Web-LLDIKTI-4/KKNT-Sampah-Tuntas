<form id="form-tambah" method="post" action="{{ url('kpicapaian/insert') }}">
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kpi" class="form-control form-control-sm">
            <option value="null">--pilih Key performance indicator--</option>
            @if($kpi)
                @foreach($kpi as $item)
                    <option value="{{$item->id_kpi}}">{{$item->nama_kpi}}</option>
                @endforeach
            @endif
        </select>
        <label>Key performance indicator</label>
    </div>
    <div id="resulttargetkpi">
        <div class="form-group form-floating form-floating-outline mb-6">
            <select name="id_target" class="form-control form-control-sm">
                <option value="null">--pilih dulu Key performance indicator--</option>
            </select>
            <label>Target Key performance indicator</label>
        </div>
    </div>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="status_capaian" class="form-control form-control-sm">
            @foreach(array('Y','N') as $item)
                <option value="{{$item}}">{{$item}}</option>
            @endforeach
        </select>
        <label>Capaian</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="tautan" class="form-control form-control-sm">
        <label>Tautan</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="permasalahan" class="form-control"></textarea>
        <label>Permasalahan</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="solusi" class="form-control"></textarea>
        <label>Solusi</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="kendala" class="form-control"></textarea>
        <label>Kendala</label>
    </div>
    <hr>
    <button type="submit" id="btnSubmit_form-tambah" class="btn btn-sm btn-primary">Simpan</button>
</form>
