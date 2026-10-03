<form id="form-ubah" method="post" action="{{ url('kpi/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_kpi" value="{{$data->id_kpi}}">
    <x-form.input name="nama_kpi" label="Nama KPI" :value="$data->nama_kpi" input-class="form-control" required maxlength="255" />
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
