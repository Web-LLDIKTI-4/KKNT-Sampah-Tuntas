<form id="form-tambah" method="post" action="{{ url('picdlh/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kecamatan" class="form-control" required>
            @foreach($kecamatan as $item)
                <option value="{{ $item->id_kecamatan }}">{{ $item->kecamatan }}</option>
            @endforeach
        </select>
        <label>Kecamatan</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="nama" class="form-control form-control-sm" required maxlength="200">
        <label>Nama</label>
    </div>
    <div class="row">
        <div class="form-group col-12 col-md form-floating form-floating-outline mb-6">
            <input type="tel" name="kontak" class="form-control form-control-sm" required maxlength="50" inputmode="tel" autocomplete="off">
            <label>Kontak (No. HP)</label>
        </div>
        <div class="form-group col-12 col-md form-floating form-floating-outline mb-6">
            <select name="keterangan" class="form-control" required>
                @foreach($keterangan as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <label>Keterangan</label>
        </div>
    </div>
    <hr>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
