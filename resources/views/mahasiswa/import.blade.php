<div class="alert alert-info">
    Gunakan <a href="{{ url('assets/format_doc/format_mahasiswa.xlsx') }}">template</a> ini untuk import data mahasiswa
</div>

<form method="post" action="{{ url('mahasiswa/prosesimport') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="file" name="file" class="form-control form-control-sm" required accept=".xlsx,.xls,.csv">
    <hr>
    <x-btn-save formId="form-import" class="btn btn-primary btn-sm">Simpan</x-btn-save>
</form>