<div class="alert alert-info">
Gunakan <a href="{{ url('assets/format_doc/format_mahasiswa.xlsx') }}">template</a> untuk upload data
</div>
<form method="post" action="{{ url('mahasiswa/prosesimport') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="file" name="file" class="form-control form-control-sm">
    <hr>
    <button type="submit" class="btn btn-primary btn-sm"><i class="ri-save-fill me-1"></i> Simpan</button>
</form>