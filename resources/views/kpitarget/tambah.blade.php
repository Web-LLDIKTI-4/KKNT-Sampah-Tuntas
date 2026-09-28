<form id="form-tambah" method="post" action="{{ url('kpitarget/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kpi" class="form-control form-control-sm" required>
            @if($kpi)
                @foreach($kpi as $item)
                    <option value="{{$item->id_kpi}}">{{$item->nama_kpi}}</option>
                @endforeach
            @endif
        </select>
        <label>Nama KPI</label>
    </div>
    <div class="row">
        <div class="form-group col-md-4 form-floating form-floating-outline mb-6">
            <input type="text" name="tahapan" class="form-control form-control-sm" required maxlength="50">
            <label>Tahapan</label>
        </div>
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="nama_kpitarget" class="form-control form-control-sm" required maxlength="200">
            <label>Kegiatan</label>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-6 form-floating form-floating-outline mb-6">
            <input type="number" name="target" class="form-control form-control-sm" required min="0" max="999999" step="any">
            <label>Target</label>
        </div>
        <div class="form-group col-md-6 form-floating form-floating-outline mb-6">
            <input type="text" name="satuan" class="form-control form-control-sm" required maxlength="50" placeholder="%, Kegiatan, % titik pemilahan">
            <label>Satuan</label>
        </div>
    </div>
    <hr>
    <x-btn-save formId="form-tambah">
        Simpan
    </x-btn-save>
</form>
