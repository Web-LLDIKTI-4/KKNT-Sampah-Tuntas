<form method="post" id="form-tambah" action="{{ url('dplkonversinilai/insert') }}">
@csrf
@method('PUT')
<div class="form-group form-floating form-floating-outline mb-6">
    <select name="id_mahasiswa" class="form-control">
    @if($mahasiswa)
        @foreach($mahasiswa as $item)
            <option value="{{$item->mahasiswa->id_mahasiswa}}">{{$item->mahasiswa->nama}} | {{$item->mahasiswa->sp->nm_lemb}}</option>
        @endforeach
    @endif
    </select>
    <label>Pilih Mahasiswa</label>
</div>
<div class="row">   
    <div class="form-group col-md-8 form-floating form-floating-outline mb-6">
        <input type="text" class="form-control" name="matakuliah">
        <label>Matakuliah</label>
    </div>
    <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="text" class="form-control" name="sks">
        <label>SKS</label>
    </div>
</div>
<div class="row">   
    <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="text" class="form-control" name="nilai_dpl">
        <label>Nilai DPL : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div>
    <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="text" class="form-control" name="nilai_dpa">
        <label>Nilai DPA : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div>
</div>
<hr>
<button type="submit" id="btnSubmit_form-tambah" class="btn btn-primary btn-sm">Simpan</button>
</form>

