<form id="form-tambah" method="post" action="{{ url('pjdesa/insert') }}">
    @csrf
    @method('PUT')

    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_desa" class="form-control">
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
        <label>Desa</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="email" class="form-control">
        @if($user)
            @foreach($user as $item)
             <option value="{{$item->email}}">{{$item->name }} | {{ $item->mahasiswa->sp->nm_lemb ?? 'Data tidak tersedia'; }}</option>
            @endforeach
        @endif
        </select>
        <label>PJ Desa</label>
    </div>
    <hr>
    <x-btn-save formId="form-tambah"><i class="tf-icons ri-save-3-fill ri-16px me-1"></i>Simpan</x-btn-save>
</form>
