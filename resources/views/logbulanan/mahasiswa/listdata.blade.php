<div class="col-12 table-responsive">
<table class="table table-bordered table-sm" id="dataTable">
    <thead>
        <tr>
            <th class="text-center" width="1">No</th>
            <th class="text-center">Tahun</th>
            <th class="text-center">Bulan</th>
            <th class="text-center">Tautan</th>
            <th class="text-center" width="1">Aksi</th>
            <th class="text-center">Nilai</th>
            <th class="text-center">Hasil Verifikasi</th>
        </tr>
    </thead>
    <tbody>
        @if($laporan->isEmpty())
            
        @else
            @foreach($laporan as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="text-center">{{ $row->tahun }}</td>
                    <td class="text-center">{{ Carbon\Carbon::create()->month($row->bulan)->format('F') }}</td>
                    <td>{{ $row->tautan }}</td>
                    <td class="text-center">
                        <div class="d-flex">
                            <form method="post" id="form-bulan-{{$row->id_logbulanan}}" action="{{ url('logbulanan/tambah') }}">
                                @csrf
                                <input type="hidden" name="tahun" value="{{$row->tahun}}">
                                <input type="hidden" name="bulan" value="{{$row->bulan}}">
                                <button type="submit" id="btnSubmit_form-bulan-{{$row->id_logbulanan}}" class="btn p-0 m-0 action-item" data-toggle="tooltip" title="" data-original-title="Quick view"><i class="ri-edit-box-line text-success"></i></button>
                            </form>

                            <form method="post" id="form-hapus-{{$row->id_logbulanan}}" action="{{ url('logbulanan/destroy') }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="id_logbulanan" value="{{$row->id_logbulanan}}">
                                <button type="submit" id="btnSubmit_form-hapus-{{$row->id_logbulanan}}" class="btn p-0 m-0 action-item text-danger ml-2" data-toggle="tooltip" title="" data-original-title="Move to trash"><i class="ri-delete-bin-3-line text-danger"></i></button>
                            </form>
                        </div>
                    </td>
                    <td class="text-center">{{$row->nilai}}</td>
                    <td>{{$row->hasil_verifikasi}}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
</div>
<script>
$(function(){
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: false,
        processing: true,
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
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
                    $("#listdata").load("{{ url('logbulanan/listdata') }}");
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