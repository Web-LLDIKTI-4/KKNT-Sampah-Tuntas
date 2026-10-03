<form id="form-tambah" method="post" action="{{ url('kpi/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <x-form.input name="nama_kpi" label="Nama KPI" input-class="form-control" required maxlength="255" />
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
