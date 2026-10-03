<form id="form-tambah" method="post" action="{{ url('pjdesa/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')

    <x-form.select name="id_desa" label="Nama Desa / Kelurahan" :placeholder="false" required>
        @if($kecamatan)
            @foreach($kecamatan as $item)
                <optgroup label="{{$item->kecamatan}}">
                    @foreach($item->desa as $row)
                        <option value="{{$row->id_desa}}">{{$row->desa}}</option>
                    @endforeach
                </optgroup>
            @endforeach
        @endif
    </x-form.select>
    <x-form.select name="email" label="Nama Ketua Kelompok" :placeholder="false" required>
            @if($user->count() > 0)
                @foreach($user as $item)
                    <option value="{{$item->email}}">{{$item->name }} | {{ $item->mahasiswa->sp->nm_lemb ?? 'Data tidak tersedia'; }}</option>
                @endforeach
            @else
                <option value="">Tidak ada data</option>
            @endif
    </x-form.select>
    <hr>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
</form>
