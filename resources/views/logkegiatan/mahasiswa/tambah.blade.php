<form id="form-tambah" method="post" action="{{ url('logkegiatan/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <fieldset class="mb-6">
        <legend class="form-label fs-6 mb-2">Anda mau mengisi pendataan sampah atau log harian? <span class="text-danger">*</span></legend>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="mode" id="mode-pendataan" value="pendataan" required>
            <label class="form-check-label" for="mode-pendataan">Pendataan Sampah</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="mode" id="mode-kegiatan" value="kegiatan">
            <label class="form-check-label" for="mode-kegiatan">Log Harian / Kegiatan</label>
        </div>
        {{-- Also shows input limit errors keyed on mode --}}
        <div><span id="mode_error" class="text-danger"></span></div>
    </fieldset>
    {{-- Shared by both modes --}}
    <div id="tanggal-field" class="form-group form-floating form-floating-outline mb-6 d-none">
        <input type="date" name="tanggal" class="form-control form-control-sm" required max="{{ date('Y-m-d') }}">
        <label>Tanggal <span class="text-danger">*</span></label>
        <span id="tanggal_error" class="text-danger"></span>
    </div>
    {{-- Disabled fieldset: inputs skip validation and are not submitted --}}
    <fieldset id="pendataan-fields" class="d-none" disabled>
        @include('pendataanpemilahan._fields', ['withTanggal' => false])
    </fieldset>
    <fieldset id="log-kegiatan-fields" class="d-none" disabled>
        @include('logkegiatan.mahasiswa._fields', ['withTanggal' => false])
    </fieldset>
    <x-button.save formId="form-tambah" disabled>
        Simpan
    </x-button.save>
</form>
<script>
    $('#form-tambah').on('change', 'input[name="mode"]', function () {
        var pendataan = this.value === 'pendataan';
        $('#tanggal-field').removeClass('d-none');
        $('#pendataan-fields').toggleClass('d-none', !pendataan).prop('disabled', !pendataan);
        $('#log-kegiatan-fields').toggleClass('d-none', pendataan).prop('disabled', pendataan);
        $('#btnSubmit_form-tambah').prop('disabled', false);
    });
</script>
