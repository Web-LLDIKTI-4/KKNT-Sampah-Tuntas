<form method="post" id="form-tambah" action="{{ url('dplfreeform/insert') }}" data-ajax-form>
@csrf
@method('PUT')
<x-form.select name="id_mahasiswa" label="Pilih Mahasiswa" :placeholder="false" required>
    @if($mahasiswa)
        @foreach($mahasiswa as $item)
            <option value="{{$item->mahasiswa->id_mahasiswa}}">{{$item->mahasiswa->nama}} | {{$item->mahasiswa->sp->nm_lemb}}</option>
        @endforeach
    @endif
</x-form.select>
<x-form.select name="freeform" label="Free Form" :placeholder="false" required>
    @if($freeform)
        @foreach($freeform as $item)
            <option value="{{$item}}">{{$item}}</option>
        @endforeach
    @endif
</x-form.select>
<div class="row">   
    <x-form.input name="nilai_dpl" label="Nilai DPL : (A >= 80 ; B 70 -79 ; C < 70)" type="number" input-class="form-control" wrapper-class="form-group col form-floating form-floating-outline mb-6" required min="0" max="100" step="any" />
    <x-form.input name="nilai_dpa" label="Nilai DPA : (A >= 80 ; B 70 -79 ; C < 70)" type="number" input-class="form-control" wrapper-class="form-group col form-floating form-floating-outline mb-6" min="0" max="100" step="any" />
</div>
<hr>
<x-button.save formId="form-tambah">Simpan</x-button.save>
</form>

