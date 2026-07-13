<form id="form-ubah" method="post" action="{{ url('pjdesa/update') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id_pjdesa" value="{{$data->id_pjdesa}}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <select class="form-control" name="id_desa">
        @if($kecamatan)
            @foreach($kecamatan as $item)
                <optgroup label="{{$item->kecamatan}}">
                    @foreach($item->desa as $row)
                        <option  value="{{$row->id_desa}}" @if($data->id_desa == $row->id_desa) selected @endif>{{$row->desa}}</option>
                    @endforeach
                </optgroup>
            @endforeach
        @endif
        </select>
        <label>Desa</label>
       <!-- <input type="hidden" name="id_desa" value="{{$data->id_desa}}"/> -->
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="email" class="form-control">
        @if($user)
            @foreach($user as $item)
             <option value="{{$item->email}}" @if($data->email == $item->email) selected @endif>{{$item->name}} | {{$item->mahasiswa->sp->nm_lemb}}</option>
            @endforeach
        @endif
        </select>
        <label>PJ Desa</label>
    </div>
    <hr>
    <button type="submit" id="btnSubmit_form-ubah" class="btn btn-sm btn-primary">Simpan</button>
</form>