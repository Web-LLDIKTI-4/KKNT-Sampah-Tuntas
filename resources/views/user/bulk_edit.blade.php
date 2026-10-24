<form id="form-bulk-edit" method="post" action="{{ url('user/bulk-update') }}" data-ajax-form data-bulk-form data-reload="#dataTable-user">
    @csrf
    @method('PUT')
    <div data-bulk-ids></div>
    <div class="alert alert-primary py-2 mb-6" role="status">
        Diterapkan ke <strong data-bulk-form-count>0</strong> data terpilih. Hanya akun Mahasiswa, DPL, dan Perguruan Tinggi yang diubah; peran lain dilewati.
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select id="bulk_location_program" name="location_program" class="form-control form-control-sm">
            <option value="" selected>-- Tidak diubah --</option>
            @foreach($locationPrograms as $val)
                <option value="{{ $val->id }}">{{ $val->nama_lokasi }}</option>
            @endforeach
        </select>
        <label for="bulk_location_program">Lokasi Program</label>
        <span id="location_program_error" class="text-danger"></span>
    </div>
    <hr>
    <x-button.save formId="form-bulk-edit">Terapkan</x-button.save>
</form>
<script>window.BulkActions && BulkActions.fillForm($('#form-bulk-edit'));</script>
