<form id="form-tambah" method="post" action="{{ url('logkegiatan/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    @include('pendataanpemilahan._fields')
    <fieldset class="mb-6">
        <legend class="form-label fs-6 mb-2">Apakah ada kegiatan lain? <span class="text-danger">*</span></legend>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="ada_kegiatan_lain" id="ada-kegiatan-lain-ya" value="1" required>
            <label class="form-check-label" for="ada-kegiatan-lain-ya">Ya</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="ada_kegiatan_lain" id="ada-kegiatan-lain-tidak" value="0">
            <label class="form-check-label" for="ada-kegiatan-lain-tidak">Tidak</label>
        </div>
        <div><span id="ada_kegiatan_lain_error" class="text-danger"></span></div>
    </fieldset>
    {{-- Disabled fieldset: inputs skip validation and are not submitted --}}
    <fieldset id="log-kegiatan-fields" class="d-none" disabled>
        <h6 class="mb-4">Kegiatan Lain</h6>
        @include('logkegiatan.mahasiswa._fields', ['withTanggal' => false])
    </fieldset>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
<script>
    $('#form-tambah').on('change', 'input[name="ada_kegiatan_lain"]', function () {
        var show = this.value === '1';
        $('#log-kegiatan-fields').toggleClass('d-none', !show).prop('disabled', !show);
    });
</script>
