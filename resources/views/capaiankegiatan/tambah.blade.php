<form id="form-tambah" method="post" action="{{ url('capaiankegiatan/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kategori" class="form-control form-control-sm" required>
            <option value="">--pilih Kategori Kegiatan--</option>
            @if($kategoriKegiatan)
                @foreach($kategoriKegiatan as $item)
                    <option value="{{$item->id_kategori}}">{{$item->nama_kategori}}</option>
                @endforeach
            @endif
        </select>
        <label>Kategori Kegiatan</label>
    </div>

    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="date" name="bulan" required max="{{ now()->toDateString() }}" class="form-control form-control-sm">
        <label>Tanggal Capaian</label>
        <div class="form-text">Capaian disimpan per bulan (1 capaian per bulan).</div>
        <span id="bulan_error" class="text-danger"></span>
    </div>

    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="permasalahan" class="form-control" required maxlength="5000"></textarea>
        <label>Permasalahan</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="solusi" class="form-control" required maxlength="5000"></textarea>
        <label>Solusi</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="kendala" class="form-control" required maxlength="5000"></textarea>
        <label>Kebutuhan Dukungan</label>
    </div>

    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="status_capaian" class="form-control form-control-sm" required>
            @php
                $statusCapaian = [
                    [
                        'status' => 'Y',
                        'label' => 'Sudah'
                    ],
                    [
                        'status' => 'P',
                        'label' => 'Proses'
                    ],
                    [
                        'status' => 'N',
                        'label' => 'Belum'
                    ],
                ];
            @endphp

            @foreach($statusCapaian as $item)
                <option value="{{$item['status']}}">{{$item['label']}}</option>
            @endforeach
        </select>
        <label>Tindak Lanjut</label>
    </div>

    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="url" name="tautan" required maxlength="2000" placeholder="https://" class="form-control form-control-sm">
        <label>Tautan</label>
    </div>
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
