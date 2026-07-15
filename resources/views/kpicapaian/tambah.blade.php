<form id="form-tambah" method="post" action="{{ url('kpicapaian/insert') }}">
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kpi" class="form-control form-control-sm">
            <option value="null">--pilih KPI--</option>
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
            <select name="id_target" class="form-control form-control-sm">
                <option value="null">--pilih dulu KPI--</option>
            </select>
            <label>Target KPI</label>
        </div>
    </div>
    
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="permasalahan" class="form-control"></textarea>
        <label>Permasalahan</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="solusi" class="form-control"></textarea>
        <label>Solusi</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="kendala" class="form-control"></textarea>
        <label>Kebutuhan Dukungan</label>
    </div>

    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="status_capaian" class="form-control form-control-sm">
            @php
                $statusCapaian = [
                    {
                        'status' => 'Y',
                        'label' => 'Sudah Selesai'
                    },
                    {
                        'status' => 'P',
                        'label' => 'Proses'
                    },
                    {
                        'status' => 'N',
                        'label' => 'Belum Ditindaklanjuti'
                    },
                ]
            @endphp
            @foreach($statusCapaian as $item)
                <option value="{{$item['status']}}">{{$item['label']}}</option>
            @endforeach
        </select>
        <label>Tindak Lanjut</label>
    </div>

    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" name="tautan" class="form-control form-control-sm">
        <label>Tautan</label>
    </div>
    <hr>
    <x-btn-save formId="form-tambah">Simpan</x-btn-save>
</form>
