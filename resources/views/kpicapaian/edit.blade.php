<form id="form-ubah" method="post" action="{{ url('kpicapaian/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_capaian" value="{{$data->id_capaian}}">
    <x-form.select name="id_kpi" label="Nama KPI" input-class="form-control form-control-sm" :placeholder="false" required>
            @if($kpi)
                @foreach($kpi as $item)
                    <option value="{{$item->id_kpi}}" @if($data->id_kpi == $item->id_kpi) selected @endif>{{$item->nama_kpi}}</option>
                @endforeach
            @endif
    </x-form.select>

    <x-form.textarea name="permasalahan" label="Permasalahan" :value="$data->permasalahan" required maxlength="5000" />
    <x-form.textarea name="solusi" label="Solusi" :value="$data->solusi" required maxlength="5000" />
    <x-form.textarea name="kendala" label="Kebutuhan Dukungan" :value="$data->kendala" required maxlength="5000" />

    <x-form.select name="status_capaian" label="Tindak Lanjut" input-class="form-control form-control-sm" :placeholder="false" required>
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
    </x-form.select>

    <x-form.input name="tautan" label="Tautan" type="url" :value="$data->tautan" required maxlength="2000" placeholder="https://" />
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
    