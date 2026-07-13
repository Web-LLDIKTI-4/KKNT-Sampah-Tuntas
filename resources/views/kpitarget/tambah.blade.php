<form id="form-tambah" method="post" action="{{ url('kpitarget/insert') }}">
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kpi" class="form-control form-control-sm">
            @if($kpi)
                @foreach($kpi as $item)
                    <option value="{{$item->id_kpi}}">{{$item->nama_kpi}}</option>
                @endforeach
            @endif
        </select>
        <label>Key performance indicator</label>
    </div>
    <div class="row">
        <div class="form-group col-md-4 form-floating form-floating-outline mb-6">
            <input type="text" name="tahapan" class="form-control form-control-sm">
            <label>Tahapan</label>
        </div>
        <div class="form-group col form-floating form-floating-outline mb-6">
            <input type="text" name="nama_kpitarget" class="form-control form-control-sm">
            <label>Target Key performance indicator</label>
        </div>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="persen" class="form-control form-control-sm">
        <label>Percen</label>
    </div>
    <hr>
    <x-btn-save formId="form-tambah"><i class="ri-save-3-fill ri-16px me-1"></i> Simpan</x-btn-save>
</form>
