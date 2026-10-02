<form id="form-tambah" method="post" action="{{ url('kpicapaian/insert') }}" data-ajax-form>
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
    <div id="resulttargetkpi">
        <div class="form-group form-floating form-floating-outline mb-6">
            <select name="id_target" class="form-control form-control-sm" required>
                <option value="">--pilih dulu KPI--</option>
                @if ($kpitarget)
                    @foreach ($kpitarget as $item)
                        <option value="{{$item->id_target}}" data-satuan="{{$item->satuan}}" data-target="{{ (float) $item->target }}">{{$item->kegiatan}} (Target {{ \App\Models\Kpicapaian::formatAngka($item->target) }} {{$item->satuan}})</option>
                    @endforeach
                @endif
            </select>
            <label>Kegiatan</label>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-8 form-floating form-floating-outline mb-6">
            <input type="number" name="realisasi" class="form-control form-control-sm" required min="0" max="9999999999" step="any">
            <label>Realisasi</label>
        </div>
        <div class="form-group col-4 form-floating form-floating-outline mb-6">
            <input type="text" class="form-control form-control-sm" data-satuan-realisasi value="" readonly tabindex="-1">
            <label>Satuan</label>
        </div>
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
                        'label' => 'Sudah Selesai'
                    ],
                    [
                        'status' => 'P',
                        'label' => 'Proses'
                    ],
                    [
                        'status' => 'N',
                        'label' => 'Belum Ditindaklanjuti'
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
