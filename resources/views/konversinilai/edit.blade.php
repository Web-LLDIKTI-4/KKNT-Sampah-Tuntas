<div class="divider">
  <div class="divider-text">{{$data->mahasiswa->nim}} | {{$data->mahasiswa->nama}}</div>
</div>
<form method="post" id="form-ubah" action="{{ url('dplkonversinilai/update') }}">
@csrf
@method('PUT')
<input type="hidden" name="id_mahasiswa" value="{{$data->id_mahasiswa}}">
<input type="hidden" name="id_konversi" value="{{$data->id_konversi}}">
<div class="row">   
    <div class="form-group col-md-8 form-floating form-floating-outline mb-6">
        <input type="text" class="form-control" name="matakuliah" value="{{$data->matakuliah}}">
        <label>Matakuliah</label>
    </div>
    <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="text" class="form-control" name="sks" value="{{$data->sks}}">
        <label>SKS</label>
    </div>
</div>
<div class="row">   
    {{-- <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="text" class="form-control" name="nilai_dpl" value="{{$data->nilai_dpl}}">
        <label>Nilai DPL : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div> --}}
    <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="text" class="form-control" name="nilai_dpa" value="{{$data->nilai_dpa}}">
        <label>Nilai DPA : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div>
</div>
<hr>
<x-btn-save formId="form-ubah">Simpan</x-btn-save>
</form>

