<form id="form-ubah" method="post" action="{{ url('mahasiswa/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_mahasiswa" value="{{ $data->id_mahasiswa }}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" id="edit_nim" name="nim" class="form-control form-control-sm" placeholder="NIM" required maxlength="100" value="{{ $data->nim }}">
        <label for="edit_nim">NIM</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="number" id="edit_tahun_masuk" name="tahun_masuk" class="form-control form-control-sm" placeholder="Tahun Masuk" min="1990" max="{{ date('Y') + 1 }}" value="{{ $data->tahun_masuk }}">
        <label for="edit_tahun_masuk">Tahun Masuk</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" id="edit_nama" name="nama" class="form-control form-control-sm" placeholder="Nama" required maxlength="255" value="{{ $data->nama }}">
        <label for="edit_nama">Nama</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        @if ($canEditEmail)
            <input type="email" id="edit_email" name="email" class="form-control form-control-sm" placeholder="Email" required maxlength="100" value="{{ $data->email }}" aria-describedby="edit_email_hint">
            <label for="edit_email">Email</label>
            <small id="edit_email_hint" class="d-block text-muted mt-1">Mahasiswa belum memiliki akun maupun data terkait, email masih dapat diubah.</small>
        @else
            <input type="email" id="edit_email" class="form-control form-control-sm" placeholder="Email" value="{{ $data->email }}" readonly aria-describedby="edit_email_hint">
            <label for="edit_email">Email</label>
            <small id="edit_email_hint" class="d-block text-muted mt-1">Email tidak dapat diubah karena terhubung dengan akun, ketua kelompok, atau DPL pembimbing</small>
        @endif
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" id="edit_phone" name="phone" class="form-control form-control-sm" placeholder="No. HP" maxlength="50" value="{{ $data->phone }}">
        <label for="edit_phone">No. HP</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" id="edit_prodi" name="prodi" class="form-control form-control-sm" placeholder="Program Studi" maxlength="100" value="{{ $data->prodi }}">
        <label for="edit_prodi">Program Studi</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select id="edit_kodept" name="kodept" class="form-control form-control-sm" required>
            <option value="">-- Pilih Perguruan Tinggi --</option>
            @foreach($perguruanTinggi as $npsn => $nmLemb)
                <option value="{{ $npsn }}" @selected((string) $data->kodept === (string) $npsn)>{{ $nmLemb }}</option>
            @endforeach
        </select>
        <label for="edit_kodept">Perguruan Tinggi</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select id="edit_location_program" name="location_program" class="form-control form-control-sm">
            <option value="" @selected(empty($data->location_program))>Belum Ditentukan</option>
            @foreach($lokasiProgram as $id => $namaLokasi)
                <option value="{{ $id }}" @selected((string) $data->location_program === (string) $id)>{{ $namaLokasi }}</option>
            @endforeach
        </select>
        <label for="edit_location_program">Lokasi Program KKN</label>
    </div>
    <hr>
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
