<form id="tambahpilih" method="post" action="{{ url('perguruantinggi/insert') }}">
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="kodept" class="form-control" placeholder="041047">
        <label>Masukan Kode Perguruan Tinggi</label>
    </div>
    <x-btn-save formId="tambahpilih"><span class="tf-icons ri-save-3-fill ri-16px me-1"></span> Simpan</x-btn-save>
</form>
