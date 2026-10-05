<form id="form-tambah" method="post" action="{{ url('kpicapaian/insert') }}" data-ajax-form data-sampah-form>
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kpi" class="form-control form-control-sm" required>
            <option value="">--pilih KPI--</option>
            @if($kpi)
                @foreach($kpi as $item)
                    <option value="{{$item->id_kpi}}">{{$item->nama_kpi}}</option>
                @endforeach
            @endif
        </select>
        <label>Nama KPI</label>
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
    @include('kpicapaian._sampah')
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
