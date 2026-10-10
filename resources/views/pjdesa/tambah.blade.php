<form id="form-tambah" method="post" action="{{ url('pjdesa/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')

    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_desa" class="form-control" required>
        @if($kecamatan)
            @foreach($kecamatan as $item)
                <optgroup label="{{$item->kecamatan}}">
                    @foreach($item->desa as $row)
                        <option value="{{$row->id_desa}}">{{$row->desa}}</option>
                    @endforeach
                </optgroup>
            @endforeach
        @endif
        </select>
        <label>Nama Kelurahan/Desa</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="email" class="form-control" required>
            @if($user->count() > 0)
                @foreach($user as $item)
                    <option value="{{$item->email}}">{{$item->name }} | {{ $item->mahasiswa->sp->nm_lemb ?? 'Data tidak tersedia'; }}</option>
                @endforeach
            @else
                <option value="">Tidak ada data</option>
            @endif
        </select>
        <label>Nama Ketua Kelompok</label>
    </div>
    <hr>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
