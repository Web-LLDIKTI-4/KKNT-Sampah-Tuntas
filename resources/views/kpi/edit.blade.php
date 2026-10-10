<form id="form-ubah" method="post" action="{{ url('kpi/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_kpi" value="{{$data->id_kpi}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="nama_kpi" class="form-control" required maxlength="255" placeholder="Pendataan" value="{{$data->nama_kpi}}">
        <label>Nama KPI</label>
    </div>
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
