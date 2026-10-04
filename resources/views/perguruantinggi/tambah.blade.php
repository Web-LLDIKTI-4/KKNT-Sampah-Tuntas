<form id="tambahpilih" method="post" action="{{ url('perguruantinggi/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <x-form.input name="kodept" label="Masukan Kode Perguruan Tinggi" input-class="form-control" placeholder="041047" />
    <x-button.save formId="tambahpilih">
        Simpan
    </x-button.save>
</form>
