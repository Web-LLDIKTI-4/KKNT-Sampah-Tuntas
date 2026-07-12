<div class="col-12 table-responsive">
<table class="table table-bordered table-sm" id="dataTable">
    <thead>
        <tr>
            <th width="1%">No</th><th>Tahun</th><th>Bulan</th><th>Tautan</th><th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @if($laporan->isEmpty())
            
        @else
            @foreach($laporan as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row->tahun }}</td>
                    <td>{{ Carbon\Carbon::create()->month($row->bulan)->format('F') }}</td>
                    <td>{{ $row->tautan }}</td>
                    <td>
                        <div class="d-flex">
                            <form method="post" id="form-bulan-{{$row->id_laporan}}" action="{{ url('dpllaporan/tambah') }}">
                                @csrf
                                <input type="hidden" name="tahun" value="{{$row->tahun}}">
                                <input type="hidden" name="bulan" value="{{$row->bulan}}">
                                <button type="submit" id="btnSubmit_form-bulan-{{$row->id_laporan}}" class="btn p-0 m-0 action-item" data-toggle="tooltip" title="" data-original-title="Quick view"><i class="ri-edit-box-line text-success"></i></button>
                            </form>

                            <form method="post" id="form-hapus-{{$row->id_laporan}}" action="{{ url('dpllaporan/destroy') }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="id_laporan" value="{{$row->id_laporan}}">
                                <button type="submit" id="btnSubmit_form-hapus-{{$row->id_laporan}}" class="btn p-0 m-0 action-item text-danger ml-2" data-toggle="tooltip" title="" data-original-title="Move to trash"><i class="ri-delete-bin-3-line text-danger"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
</div>
<script>
$(function(){
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: false, // Set to true if you're processing on the server
        columnDefs: [
            { targets: 0, orderable: false } // Prevent sorting on the "No" column
            // Remove the ellipsis render function
        ]
    });
    $("[id^=form-hapus-]").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();
        var dString = $(this).serialize();
        if(confirm("yakin data akan di hapus?")){
            $.ajax({
                type:'post',
                url:action,
                data:dString,
                beforeSend:function(){
                    $("#btnSubmit_"+id+"").prop("disabled",true);
                    $("#btnSubmit_"+id+"").html("<span class='spinner-grow spinner-grow-sm' role='status' aria-hidden='true'></span> Loading...");			
                },
                complete:function(){
                    $("#btnSubmit_"+id+"").prop("disabled",false);
                    $("#btnSubmit_"+id+"").html(btnHtml);	
                },
                success:function(ret){
                    $("#listdata").load("{{ url('dpllaporan/listdata') }}");                    
                },
                error:function(xhr,ajaxOptions,thrownError){
                    console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
                }			
                
            });
        }
        return false;
    });
})
</script>