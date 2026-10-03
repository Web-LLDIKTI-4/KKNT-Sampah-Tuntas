<form method="post" id="form-tambah" action="{{ url('dplkonversinilai/insert') }}" data-ajax-form>
@csrf
@method('PUT')
<x-form.select name="id_mahasiswa" label="Pilih Mahasiswa" :placeholder="false" required>
    @if($mahasiswa)
        @foreach($mahasiswa as $item)
            <option value="{{$item->mahasiswa->id_mahasiswa}}">{{$item->mahasiswa->nama}} | {{$item->mahasiswa->sp->nm_lemb}}</option>
        @endforeach
    @endif
</x-form.select>
<div class="row">   
    <x-form.input name="matakuliah" label="Matakuliah" input-class="form-control" wrapper-class="form-group col-md-8 form-floating form-floating-outline mb-6" required maxlength="150" />
    <x-form.input name="sks" label="SKS" type="number" input-class="form-control" wrapper-class="form-group col form-floating form-floating-outline mb-6" required min="1" max="24" />
</div>
<div class="row">   
    <x-form.input name="nilai_dpl" label="Nilai DPL : (A >= 80 ; B 70 -79 ; C < 70)" type="number" input-class="form-control" wrapper-class="form-group col form-floating form-floating-outline mb-6" required min="0" max="100" step="any" />
    {{-- <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="number" class="form-control" name="nilai_dpa" min="0" max="100" step="any">
        <label>Nilai DPA : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div> --}}
</div>
<hr>
<x-button.save formId="form-tambah">Simpan</x-button.save>
</form>

