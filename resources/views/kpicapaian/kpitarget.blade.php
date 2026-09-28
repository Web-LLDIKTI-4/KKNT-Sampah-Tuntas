<div class="form-group form-floating form-floating-outline mb-6">
<select name="id_target" class="form-control form-control-sm" required>
    @if(!$kpitarget->isEmpty())
        @foreach($kpitarget as $item)
            <option value="{{$item->id_target}}" data-satuan="{{$item->satuan}}">{{$item->tahapan}} | {{$item->nama_kpitarget}} (Target {{ AppModelsKpicapaian::formatAngka($item->target) }} {{$item->satuan}})</option>
        @endforeach
    @else
        <option value="">--pilih dulu Target KPI--</option>    
    @endif
</select>
<label>Tahapan | Kegiatan</label>

</div>
