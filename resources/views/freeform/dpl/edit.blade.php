<h6 class="float-end">{{$data->mahasiswa->nim}} | {{$data->mahasiswa->nama}}</h6><hr>
<form method="post" id="form-ubah" action="{{ url('dplfreeform/update') }}" data-ajax-form>
@csrf
@method('PUT')
<input type="hidden" name="id_mahasiswa" value="{{$data->id_mahasiswa}}">
<input type="hidden" name="id_freeform" value="{{$data->id_freeform}}">
<div class="form-group form-floating form-floating-outline mb-6">
    <select name="freeform" class="form-control" required>
    @if($freeform)
        @foreach($freeform as $item)
            <option value="{{$item}}" @if($item == $data->freeform) selected @endif>{{$item}}</option>
        @endforeach
    @endif
    </select>
    <label>Free Form</label>
</div>
<div class="row">   
    <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="number" class="form-control" name="nilai_dpl" required min="0" max="100" step="any" value="{{$data->nilai_dpl}}">
        <label>Nilai DPL : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div>
    <div class="form-group col form-floating form-floating-outline mb-6">
        <input type="number" class="form-control" name="nilai_dpa" min="0" max="100" step="any" value="{{$data->nilai_dpa}}">
        <label>Nilai DPA : (A >= 80 ; B 70 -79 ; C < 70)</label>
    </div>
</div>
<hr>
<x-button.save formId="form-ubah">Simpan</x-button.save>
</form>

