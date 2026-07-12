<form id="form-ubah" method="post" action="{{ url('kecamatan/update') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id_kecamatan" value="{{$data->id_kecamatan}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="kecamatan" class="form-control form-control-sm" value="{{ $data->kecamatan }}">
        <label>Kecamatan</label>
    </div>
    <hr>
    <button type="submit" id="btnSubmit_form-tambah" class="btn btn-sm btn-primary"><i class="tf-icons ri-save-3-fill ri-16px me-1"></i>Simpan</button>
</form>
    