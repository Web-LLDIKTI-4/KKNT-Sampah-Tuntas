<div class="form-group form-floating form-floating-outline mb-6">
<select name="id_target" class="form-control form-control-sm">
    @if(!$kpitarget->isEmpty())
        @foreach($kpitarget as $item)
            <option value="{{$item->id_target}}">{{$item->tahapan}} | {{$item->nama_kpitarget}} ({{$item->persen}} Persen)</option>
        @endforeach
    @else
        <option value="null">--pilih dulu Target KPI--</option>    
    @endif
</select>
<label>Target KPI</label>

</div>
