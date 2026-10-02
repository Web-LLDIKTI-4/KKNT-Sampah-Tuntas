<form id="form-ubah" method="post" action="{{ url('kpicapaian/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_capaian" value="{{$data->id_capaian}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kpi" class="form-control form-control-sm" required>
            @if($kpi)
                @foreach($kpi as $item)
                    <option value="{{$item->id_kpi}}" @if($data->id_kpi == $item->id_kpi) selected @endif>{{$item->nama_kpi}}</option>
                @endforeach
            @endif
        </select>
        <label>Nama KPI</label>
    </div>
    <div id="resulttargetkpi">
        <div class="form-group form-floating form-floating-outline mb-6">
            <select name="id_target" class="form-control form-control-sm" required>
                @if($kpitarget)
                    @foreach($kpitarget as $item)
                        <option value="{{$item->id_target}}" data-satuan="{{$item->satuan}}" data-target="{{ (float) $item->target }}" @if($data->id_target == $item->id_target) selected @endif>{{$item->kegiatan}} (Target {{ \App\Models\Kpicapaian::formatAngka($item->target) }} {{$item->satuan}})</option>
                    @endforeach
                @endif
            </select>
            <label>Kegiatan</label>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-8 form-floating form-floating-outline mb-6">
            <input type="number" name="realisasi" class="form-control form-control-sm" required min="0" max="9999999999" step="any" value="{{ $data->realisasi !== null ? (float) $data->realisasi : '' }}">
            <label>Realisasi</label>
        </div>
        <div class="form-group col-4 form-floating form-floating-outline mb-6">
            <input type="text" class="form-control form-control-sm" data-satuan-realisasi value="{{ $data->target->satuan ?? '' }}" readonly tabindex="-1">
            <label>Satuan</label>
        </div>
    </div>

    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="permasalahan" class="form-control" required maxlength="5000">{{ $data->permasalahan }}</textarea>
        <label>Permasalahan</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="solusi" class="form-control" required maxlength="5000">{{ $data->solusi }}</textarea>
        <label>Solusi</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="kendala" class="form-control" required maxlength="5000">{{ $data->kendala }}</textarea>
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
                <option value="{{$item['status']}}" @if($data->status_capaian == $item['status']) selected @endif>{{$item['label']}}</option>
            @endforeach
        </select>
        <label>Tindak Lanjut</label>
    </div>

    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="url" name="tautan" required maxlength="2000" placeholder="https://" value="{{ $data->tautan }}" class="form-control form-control-sm">
        <label>Tautan</label>
    </div>
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
    