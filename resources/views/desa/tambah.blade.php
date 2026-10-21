<form id="form-tambah" method="post" action="{{ url('desa/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kecamatan" class="form-control" required>
        <option value="">--pilih--</option>
        @if($kecamatan)
            @foreach($kecamatan as $item)
                <option value="{{$item->id_kecamatan}}">{{$item->kecamatan}}</option>
            @endforeach
        @endif
        </select>
        <label>Nama Kecamatan</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="desa" class="form-control form-control-sm" required maxlength="200">
        <label>Nama Kelurahan/Desa</label>
    </div>
    <div class="form-group mb-6">
        <div class="input-group input-group-merge">
            <div class="form-floating form-floating-outline">
                <input type="number" name="baseline" id="baseline-tambah" class="form-control form-control-sm" step="0.01" min="0" max="100" inputmode="decimal" placeholder="0,00">
                <label for="baseline-tambah">Base Line (%)</label>
            </div>
            <span class="input-group-text">%</span>
        </div>
        <div class="form-text">Opsional, 0–100 dengan maksimal 2 angka desimal.</div>
    </div>
    <div class="row g-4 mb-2">
        <div class="col-12 col-sm-6">
            <div class="form-group form-floating form-floating-outline">
                <input type="number" name="latitude" class="form-control form-control-sm" step="any" min="-90" max="90" placeholder="-6.9175">
                <label>Latitude</label>
            </div>
        </div>
        <div class="col-12 col-sm-6">
            <div class="form-group form-floating form-floating-outline">
                <input type="number" name="longitude" class="form-control form-control-sm" step="any" min="-180" max="180" placeholder="107.6191">
                <label>Longitude</label>
            </div>
        </div>
    </div>
    @include('desa._map_picker')
    <hr>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
