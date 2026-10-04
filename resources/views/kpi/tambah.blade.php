<form id="form-tambah" method="post" action="{{ url('kpi/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="nama_kpi" class="form-control" required maxlength="255">
        <label>Nama KPI</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="number" name="target" class="form-control" step="0.01" min="0">
        <label>Target</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="satuan" class="form-control" maxlength="50">
        <label>Satuan</label>
    </div>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
