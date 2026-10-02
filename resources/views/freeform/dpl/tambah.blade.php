<form method="post" id="form-tambah" action="{{ url('dplfreeform/insert') }}" data-ajax-form>
@csrf
@method('PUT')
<div class="form-group form-floating form-floating-outline mb-6">
    <select name="id_mahasiswa" class="form-control" required>
    @if($mahasiswa)
        @foreach($mahasiswa as $item)
            <option value="{{$item->mahasiswa->id_mahasiswa}}">{{$item->mahasiswa->nama}} | {{$item->mahasiswa->sp->nm_lemb}}</option>
        @endforeach
    @endif
    </select>
    <label>Pilih Mahasiswa</label>
</div>
<div class="form-group form-floating form-floating-outline mb-6">
    <select name="freeform" class="form-control" required>
    @if($freeform)
        @foreach($freeform as $item)
            <option value="{{$item}}">{{$item}}</option>
        @endforeach
    @endif
    </select>
    <label>Free Form</label>
</div>
<div class="row">   
    <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="number" class="form-control" name="nilai_dpl" required min="0" max="100" step="any">
        <label>Nilai DPL : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div>
    <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="number" class="form-control" name="nilai_dpa" min="0" max="100" step="any">
        <label>Nilai DPA : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div>
</div>
<hr>
<x-button.save formId="form-tambah">Simpan</x-button.save>
</form>

