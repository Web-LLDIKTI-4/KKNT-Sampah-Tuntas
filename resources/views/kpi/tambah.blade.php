<form id="form-tambah" method="post" action="{{ url('kpi/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="nama_kpi" class="form-control" required maxlength="255">
        <label>Nama KPI</label>
    </div>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
