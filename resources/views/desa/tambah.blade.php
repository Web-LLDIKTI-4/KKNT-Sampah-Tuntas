<form id="form-tambah" method="post" action="{{ url('desa/insert') }}">
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kecamatan" class="form-control">
        <option value="">--pilih--</option>
        @if($kecamatan)
            @foreach($kecamatan as $item)
                <option value="{{$item->id_kecamatan}}">{{$item->kecamatan}}</option>
            @endforeach
        @endif
        </select>
        <label>Kecamatan</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="desa" class="form-control form-control-sm">
        <label>Desa</label>
    </div>
    <hr>
    <x-btn-save formId="form-tambah"><i class="tf-icons ri-save-3-fill ri-16px me-1"></i> Simpan</x-btn-save>
</form>