<form id="form-ubah" method="post" action="{{ url('pjdesa/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_pjdesa" value="{{$data->id_pjdesa}}">
    <x-form.select name="id_desa" label="Nama Desa / Kelurahan" :placeholder="false" required>
        @if($kecamatan)
            @foreach($kecamatan as $item)
                <optgroup label="{{$item->kecamatan}}">
                    @foreach($item->desa as $row)
                        <option  value="{{$row->id_desa}}" @if($data->id_desa == $row->id_desa) selected @endif>{{$row->desa}}</option>
                    @endforeach
                </optgroup>
            @endforeach
        @endif
    </x-form.select>
    <x-form.select name="email" label="Nama Ketua Kelompok" :placeholder="false" required>
        @if($user)
            @foreach($user as $item)
             <option value="{{$item->email}}" @if($data->email == $item->email) selected @endif>{{$item->name}} | {{$item->mahasiswa->sp->nm_lemb}}</option>
            @endforeach
        @endif
    </x-form.select>
    <hr>
    <x-button.save formId="form-ubah">
        Simpan
    </x-button.save>
</form>