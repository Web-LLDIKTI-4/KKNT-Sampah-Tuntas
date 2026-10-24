<form id="form-bulk-edit" method="post" action="{{ url('mahasiswa/bulk-update') }}" data-ajax-form data-bulk-form data-reload="#dataTable">
    @csrf
    @method('PUT')
    <div data-bulk-ids></div>
    <div class="alert alert-primary py-2 mb-6" role="status">
        Diterapkan ke <strong data-bulk-form-count>0</strong> data terpilih. Kosongkan field yang tidak ingin diubah.
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="number" id="bulk_tahun_masuk" name="tahun_masuk" class="form-control form-control-sm" placeholder="-- Tidak diubah --" min="1990" max="{{ date('Y') + 1 }}">
        <label for="bulk_tahun_masuk">Tahun Masuk</label>
        <span id="tahun_masuk_error" class="text-danger"></span>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" id="bulk_prodi" name="prodi" class="form-control form-control-sm" placeholder="-- Tidak diubah --" maxlength="100">
        <label for="bulk_prodi">Program Studi</label>
        <span id="prodi_error" class="text-danger"></span>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select id="bulk_kodept" name="kodept" class="form-control form-control-sm">
            <option value="" selected>-- Tidak diubah --</option>
            @foreach($perguruanTinggi as $npsn => $nmLemb)
                <option value="{{ $npsn }}">{{ $nmLemb }}</option>
            @endforeach
        </select>
        <label for="bulk_kodept">Perguruan Tinggi</label>
        <span id="kodept_error" class="text-danger"></span>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select id="bulk_location_program" name="location_program" class="form-control form-control-sm">
            <option value="" selected>-- Tidak diubah --</option>
            @foreach($lokasiProgram as $id => $namaLokasi)
                <option value="{{ $id }}">{{ $namaLokasi }}</option>
            @endforeach
        </select>
        <label for="bulk_location_program">Lokasi Program KKN</label>
        <span id="location_program_error" class="text-danger"></span>
    </div>
    <hr>
    <x-button.save formId="form-bulk-edit">Terapkan</x-button.save>
</form>
<script>window.BulkActions && BulkActions.fillForm($('#form-bulk-edit'));</script>
