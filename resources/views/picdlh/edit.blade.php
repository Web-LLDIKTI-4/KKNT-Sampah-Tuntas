<form id="form-ubah" method="post" action="{{ url('picdlh/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_pic_dlh" value="{{ $data->id_pic_dlh }}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="location_program" id="location_program" class="form-control">
            <option value="">-- Pilih Lokasi Program --</option>
            @foreach($lokasiProgram as $lokasi)
                <option value="{{ $lokasi->id }}" @selected($lokasi->id == $data->location_program)>{{ $lokasi->nama_lokasi }}</option>
            @endforeach
        </select>
        <label for="location_program">Lokasi Program</label>
        <div><span id="location_program_error" class="text-danger"></span></div>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kecamatan" class="form-control">
            <option value="">-- Pilih Kecamatan --</option>
            @foreach($kecamatan as $item)
                <option value="{{ $item->id_kecamatan }}" @selected($item->id_kecamatan == $data->id_kecamatan)>{{ $item->kecamatan }}</option>
            @endforeach
        </select>
        <label>Kecamatan</label>
    </div>

    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="nama" class="form-control form-control-sm" required maxlength="200" value="{{ $data->nama }}">
        <label>Nama</label>
    </div>
    <div class="row">
        <div class="form-group col-12 col-md form-floating form-floating-outline mb-6">
            <input type="tel" name="kontak" class="form-control form-control-sm" required maxlength="50" inputmode="tel" autocomplete="off" value="{{ $data->kontak }}">
            <label>Kontak (No. HP)</label>
        </div>
        <div class="form-group col-12 col-md form-floating form-floating-outline mb-6">
            <select name="keterangan" class="form-control" required>
                @foreach($keterangan as $key => $label)
                    <option value="{{ $key }}" @selected($key == $data->keterangan)>{{ $label }}</option>
                @endforeach
            </select>
            <label>Keterangan</label>
        </div>
    </div>
    <hr>
    <x-button.save formId="form-ubah">
        Simpan
    </x-button.save>
</form>
