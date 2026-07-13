<form method="post" id="form-lokasi" action="{{ url('mhsprofile/setlokasi') }}">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="form-group col form-floating form-floating-outline">
            <select name="id_desa" class="form-control">
            @if($desa)
                @foreach($desa as $item)
                    <optgroup label="{{$item->kecamatan}}">
                        @foreach($item->desa as $row)
                            <option value="{{$row->id_desa}}">{{$row->desa}}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            @endif
            </select>
            <label>Pilih Lokasi</label>
        </div>
        <div class="form-group col-md-2 form-floating form-floating-outline">
            <select name="tahun" class="form-control">
                @for($tahun=date('Y')-1; $tahun<=date('Y'); $tahun++)
                    <option value="{{$tahun}}" @if($tahun==date('Y')) selected @endif>{{$tahun}}</option>
                @endfor
            </select>
            <label>Tahun</label>
        </div>
    </div>
    <hr>
    <button type="submit" id="btnSubmit_form-lokasi" class="btn rounded-pill btn-primary btn-sm"><i class="ri-save-2-fill pe-1"></i> Simpan</button>
</form>
<script>
$(function(){
    $("#form-lokasi").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();
        var dString = $(this).serialize();        
        $.ajax({
            dataType:'json',
            type:'post',
            url:action,
            data:dString,
            beforeSend:function(){
                $("#btnSubmit_"+id+"").prop("disabled",true);
                $("#btnSubmit_"+id+"").html("<span class='spinner-border' role='status' aria-hidden='true'></span> Loading...");			
            },
            complete:function(){
                $("#btnSubmit_"+id+"").prop("disabled",false);
                $("#btnSubmit_"+id+"").html(btnHtml);	
            },
            success:function(ret){
                if(ret.success == true){
                    toastr.success(ret.message)
                    $("#resultcontent").load("{{ url('mhsprofile/data') }}");
		
                }else{
                    toastr.warning(ret.message)
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