<div class="form-group form-floating form-floating-outline mb-6">
<select name="id_target" class="form-control form-control-sm" required>
    @if(!$kpitarget->isEmpty())
        @foreach($kpitarget as $item)
            <option value="{{$item->id_target}}" data-satuan="{{$item->satuan}}" data-target="{{ (float) $item->target }}">{{$item->kegiatan}} (Target {{ \App\Models\Kpicapaian::formatAngka($item->target) }} {{$item->satuan}})</option>
        @endforeach
    @else
        <option value="">--pilih KPI terlebih dahulu--</option>    
    @endif
</select>
<label>Kegiatan</label>

</div>
