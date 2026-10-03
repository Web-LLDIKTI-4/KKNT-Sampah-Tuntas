<form id="tambahpilih" method="post" action="{{ url('perguruantinggi/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="kodept" class="form-control" placeholder="041047">
        <label>Masukan Kode Perguruan Tinggi</label>
    </div>
    <x-button.save formId="tambahpilih">
        Simpan
    </x-button.save>
</form>
