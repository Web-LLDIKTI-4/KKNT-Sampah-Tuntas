<form id="form-ubah" method="post" action="{{ url('desa/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_desa" value="{{$data->id_desa}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kecamatan" class="form-control" required>
        @if($kecamatan)
            @foreach($kecamatan as $item)
                <option value="{{$item->id_kecamatan}}" @if($data->id_kecamatan == $item->id_kecamatan) selected @endif>{{$item->kecamatan}}</option>
            @endforeach
        @endif
        </select>
        <label>Nama Kecamatan</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="desa" class="form-control form-control-sm" required maxlength="200" value="{{$data->desa}}">
        <label>Nama Kelurahan/Desa</label>
    </div>
    <div class="row g-4 mb-2">
        <div class="col-6">
            <div class="form-group form-floating form-floating-outline">
                <input type="number" name="latitude" class="form-control form-control-sm" step="any" min="-90" max="90" placeholder="-6.9175" value="{{$data->latitude}}">
                <label>Latitude</label>
            </div>
        </div>
        <div class="col-6">
            <div class="form-group form-floating form-floating-outline">
                <input type="number" name="longitude" class="form-control form-control-sm" step="any" min="-180" max="180" placeholder="107.6191" value="{{$data->longitude}}">
                <label>Longitude</label>
            </div>
        </div>
    </div>
    @include('desa._map_picker')
    <hr>
    <x-button.save formId="form-ubah">
        Simpan
    </x-button.save>
</form>

