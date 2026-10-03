<form id="form-tambah" method="post" action="{{ url('kpicapaian/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <x-form.select name="id_kpi" label="Nama KPI" input-class="form-control form-control-sm" :placeholder="false" required>
            <option value="">--pilih KPI--</option>
            @if($kpi)
                @foreach($kpi as $item)
                    <option value="{{$item->id_kpi}}">{{$item->nama_kpi}}</option>
                @endforeach
            @endif
    </x-form.select>

    <x-form.textarea name="permasalahan" label="Permasalahan" required maxlength="5000" />
    <x-form.textarea name="solusi" label="Solusi" required maxlength="5000" />
    <x-form.textarea name="kendala" label="Kebutuhan Dukungan" required maxlength="5000" />

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
                <option value="{{$item['status']}}">{{$item['label']}}</option>
            @endforeach
    </x-form.select>

    <x-form.input name="tautan" label="Tautan" type="url" required maxlength="2000" placeholder="https://" />
    <hr>
    <x-button.save formId="form-tambah">Simpan</x-button.save>
</form>
