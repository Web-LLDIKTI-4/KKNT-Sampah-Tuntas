<form method="post" id="form-lokasi" action="{{ url('mhsprofile/setlokasi') }}">
    @csrf
    @method('PUT')
    {{-- <div class="form-group form-floating form-floating-outline mb-6">
        <select  id="location_program" class="form-control form-control-sm select2" name="location_program" disabled="false" required>
            <option value="" selected>--pilih--</option>
            @foreach($locationPrograms as $val)
                <option value="{{ $val->id }}" @if(auth()->user()->location_program == $val->id) selected @endif>{{$val->nama_lokasi}}</option>
            @endforeach
        </select>
        <label>Lokasi Program</label>
        <span id="location_program_error" class="text-danger"></span>
    </div> --}}

    <div class="row">
        <x-form.select name="id_desa" label="Pilih Desa / Kelurahan" input-class="form-control select2" wrapper-class="form-group col form-floating form-floating-outline" :placeholder="false" required>
                <option value="" selected>--pilih--</option>
            @if($desa)
                @foreach($desa as $item)
                    <optgroup label="Kecamatan : {{$item->kecamatan}}">
                        @foreach($item->desa as $row)
                            <option value="{{$row->id_desa}}" @selected($desaTerpilih === $row->id_desa)>Desa / Kelurahan : {{$row->desa}}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            @endif
        </x-form.select>
        <x-form.select name="tahun" label="Tahun" wrapper-class="form-group col-md-2 form-floating form-floating-outline" :placeholder="false" required data-no-search>
                @for($tahun=date('Y')-1; $tahun<=date('Y'); $tahun++)
                    <option value="{{$tahun}}" @selected($tahun == $tahunTerpilih)>{{$tahun}}</option>
                @endfor
        </x-form.select>
    </div>
    <hr>
    <x-button.save formId="form-lokasi" class="rounded-pill">
        Simpan
    </x-button.save>
</form>
<script>
$(function(){
    $('.select2').select2({
       dropdownParent: $('#modalku')
    });

    $("#form-lokasi").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var dString = $(this).serialize();        
        $.ajax({
            dataType:'json',
            type:'post',
            url:action,
            data:dString,
            beforeSend:function(){
                btnLoading($("#btnSubmit_" + id), true);			
            },
            complete:function(){
                btnLoading($("#btnSubmit_" + id), false);	
            },
            success:function(ret){
                if(ret.success == true){
                    toastr.success(ret.message)
                    $("#resultcontent").load("{{ url('mhsprofile/data') }}");
		
                }else{
                    toastr.warning(ret.errors ? Object.values(ret.errors).flat().join('<br>') : ret.message)
                }
            },
            error:function(xhr,ajaxOptions,thrownError){
                console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
            }			
            
        });
        return false;
    });
})
</script>