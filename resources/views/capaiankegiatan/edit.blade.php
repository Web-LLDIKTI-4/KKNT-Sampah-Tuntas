<form id="form-ubah" method="post" action="{{ url('capaiankegiatan/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_capaian" value="{{$data->id_capaian}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kategori" class="form-control form-control-sm" required>
            @if($kategoriKegiatan)
                @foreach($kategoriKegiatan as $item)
                    <option value="{{$item->id_kategori}}" @if($data->id_kategori == $item->id_kategori) selected @endif>{{$item->nama_kategori}}</option>
                @endforeach
            @endif
        </select>
        <label>Kategori Kegiatan</label>
    </div>

    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="date" name="bulan" required max="{{ now()->toDateString() }}" value="{{ $data->bulan ? \Illuminate\Support\Carbon::parse($data->bulan)->format('Y-m-d') : '' }}" class="form-control form-control-sm">
        <label>Tanggal Capaian</label>
        <div class="form-text">Capaian disimpan per bulan (1 capaian per bulan).</div>
        <span id="bulan_error" class="text-danger"></span>
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
    <x-button.save formId="form-ubah">Simpan</x-button.save>
</form>
    