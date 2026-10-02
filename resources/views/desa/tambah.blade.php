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
        <label>Nama Desa / Kelurahan</label>
    </div>
    <hr>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>