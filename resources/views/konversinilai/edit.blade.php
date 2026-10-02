<div class="divider">
  <div class="divider-text">{{$data->mahasiswa->nim}} | {{$data->mahasiswa->nama}}</div>
</div>
<form method="post" id="form-ubah" action="{{ url('dplkonversinilai/update') }}" data-ajax-form>
@csrf
@method('PUT')
<input type="hidden" name="id_mahasiswa" value="{{$data->id_mahasiswa}}">
<input type="hidden" name="id_konversi" value="{{$data->id_konversi}}">
<div class="row">   
    <div class="form-group col-md-8 form-floating form-floating-outline mb-6">
        <input type="text" class="form-control" name="matakuliah" required maxlength="150" value="{{$data->matakuliah}}">
        <label>Matakuliah</label>
    </div>
    <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="number" class="form-control" name="sks" required min="1" max="24" value="{{$data->sks}}">
        <label>SKS</label>
    </div>
</div>
<div class="row">   
    <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="number" class="form-control" name="nilai_dpl" required min="0" max="100" step="any" value="{{$data->nilai_dpl}}">
        <label>Nilai DPL : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div>
    {{-- <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="number" class="form-control" name="nilai_dpa" min="0" max="100" step="any" value="{{$data->nilai_dpa}}">
        <label>Nilai DPA : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div> --}}
</div>
<hr>
<x-button.save formId="form-ubah">Simpan</x-button.save>
</form>

