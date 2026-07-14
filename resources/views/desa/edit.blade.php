<form id="form-ubah" method="post" action="{{ url('desa/update') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id_desa" value="{{$data->id_desa}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kecamatan" class="form-control">
        @if($kecamatan)
            @foreach($kecamatan as $item)
                <option value="{{$item->id_kecamatan}}" @if($data->id_kecamatan == $item->id_kecamatan) selected @endif>{{$item->kecamatan}}</option>
            @endforeach
        @endif
        </select>
        <label>Nama Kecamatan</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="desa" class="form-control form-control-sm" value="{{$data->desa}}">
        <label>Nama Desa / Kelurahan</label>
    </div>
    <hr>
    <x-btn-save formId="form-ubah">
        Simpan
    </x-btn-save>
</form>

    