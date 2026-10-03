<div class="divider">
  <div class="divider-text">{{$data->mahasiswa->nim}} | {{$data->mahasiswa->nama}}</div>
</div>
<form method="post" id="form-ubah" action="{{ url('dplkonversinilai/update') }}" data-ajax-form>
@csrf
@method('PUT')
<input type="hidden" name="id_mahasiswa" value="{{$data->id_mahasiswa}}">
<input type="hidden" name="id_konversi" value="{{$data->id_konversi}}">
<div class="row">   
    <x-form.input name="matakuliah" label="Matakuliah" :value="$data->matakuliah" input-class="form-control" wrapper-class="form-group col-md-8 form-floating form-floating-outline mb-6" required maxlength="150" />
    <x-form.input name="sks" label="SKS" type="number" :value="$data->sks" input-class="form-control" wrapper-class="form-group col form-floating form-floating-outline mb-6" required min="1" max="24" />
</div>
<div class="row">   
    <x-form.input name="nilai_dpl" label="Nilai DPL : (A >= 80 ; B 70 -79 ; C < 70)" type="number" :value="$data->nilai_dpl" input-class="form-control" wrapper-class="form-group col form-floating form-floating-outline mb-6" required min="0" max="100" step="any" />
    {{-- <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="number" class="form-control" name="nilai_dpa" min="0" max="100" step="any" value="{{$data->nilai_dpa}}">
        <label>Nilai DPA : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div> --}}
</div>
<hr>
<x-button.save formId="form-ubah">Simpan</x-button.save>
</form>

