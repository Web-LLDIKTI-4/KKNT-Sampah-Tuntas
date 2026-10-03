<h6 class="float-end">{{$data->mahasiswa->nim}} | {{$data->mahasiswa->nama}}</h6><hr>
<form method="post" id="form-ubah" action="{{ url('dplfreeform/update') }}" data-ajax-form>
@csrf
@method('PUT')
<input type="hidden" name="id_mahasiswa" value="{{$data->id_mahasiswa}}">
<input type="hidden" name="id_freeform" value="{{$data->id_freeform}}">
<x-form.select name="freeform" label="Free Form" :placeholder="false" required>
    @if($freeform)
        @foreach($freeform as $item)
            <option value="{{$item}}" @if($item == $data->freeform) selected @endif>{{$item}}</option>
        @endforeach
    @endif
</x-form.select>
<div class="row">   
    <x-form.input name="nilai_dpl" label="Nilai DPL : (A >= 80 ; B 70 -79 ; C < 70)" type="number" :value="$data->nilai_dpl" input-class="form-control" wrapper-class="form-group col form-floating form-floating-outline mb-6" required min="0" max="100" step="any" />
    <x-form.input name="nilai_dpa" label="Nilai DPA : (A >= 80 ; B 70 -79 ; C < 70)" type="number" :value="$data->nilai_dpa" input-class="form-control" wrapper-class="form-group col form-floating form-floating-outline mb-6" min="0" max="100" step="any" />
</div>
<hr>
<x-button.save formId="form-ubah">Simpan</x-button.save>
</form>

