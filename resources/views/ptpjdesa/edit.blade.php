<form id="form-ubah" method="post" action="{{ route('ptpjdesa.update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_pjdesa" value="{{ $pjdesa->id_pjdesa }}">
    <dl class="row mb-4 small">
        <dt class="col-sm-4 text-muted fw-normal">Ketua Kelompok</dt>
        <dd class="col-sm-8 fw-medium">{{ $pjdesa->mahasiswa->nama ?? 'Data tidak tersedia' }}</dd>
        <dt class="col-sm-4 text-muted fw-normal">Kecamatan</dt>
        <dd class="col-sm-8">{{ $pjdesa->desa->kecamatan->kecamatan ?? '-' }}</dd>
        <dt class="col-sm-4 text-muted fw-normal">Kelurahan/Desa</dt>
        <dd class="col-sm-8 mb-0">{{ $pjdesa->desa->desa ?? '-' }}</dd>
    </dl>
    <fieldset class="mb-6">
        <legend class="form-label fs-6 mb-2">Apakah kelompok ini sudah mobilisasi? <span class="text-danger">*</span></legend>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status_mobilisasi" id="mobilisasi-sudah" value="sudah" required @checked($pjdesa->status_mobilisasi === 'sudah')>
            <label class="form-check-label" for="mobilisasi-sudah">Sudah</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status_mobilisasi" id="mobilisasi-belum" value="belum" @checked($pjdesa->status_mobilisasi === 'belum')>
            <label class="form-check-label" for="mobilisasi-belum">Belum</label>
        </div>
        <div><span id="status_mobilisasi_error" class="text-danger"></span></div>
    </fieldset>
    <hr>
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
