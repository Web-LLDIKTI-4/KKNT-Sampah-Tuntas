<div class="divider">
  <div class="divider-text"><h4><i class="ri-calendar-todo-line"></i> {{ date("Y-m-d") }}</h4></div>
</div>
<span id="tanggal_error" class="text-danger d-flex justify-content-center"></span>
<form id="form-tambah" method="post" action="{{ url('logkehadiran/insertizin') }}">
    @csrf
    @method('PUT')
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="status_kehadiran" class="form-control form-control-sm">
            @if($status_kehadiran) 
                @foreach($status_kehadiran as $row)
                    <option value="{{$row}}">{{$row}}</option>
                @endforeach
            @endif
        </select>
        <label>Status Izin</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="keterangan" class="form-control"></textarea>
        <label>Keterangan</label>
    </div>
    <hr>
    <button type="submit" id="btnSubmit_form-tambah" class="btn btn-sm btn-primary">Simpan</button>
</form>
        
<script>
    $(function(){
      $("#form-tambah").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();
        var dString = $(this).serialize();
        
        $("#tanggal_error").html('');
        $.ajax({
          dataType:'json',
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
            var table = $('#dataTable').DataTable(); // Menginisialisasi objek tabel
            // Memuat ulang data tabel secara manual
            table.ajax.reload();
            if(ret.success == true){
              $("#modalku").modal("hide");		
              toastr.success(ret.message)			
              
            }else{
                $.each(ret.errors, function(key, value) {
                        $("#" + key + "_error").html(value[0]); // Menampilkan pesan error di dalam field yang sesuai
                });
                toastr.warning(ret.message)	
            }
          },
          error:function(xhr,ajaxOptions,thrownError){
            alert(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
          }			
        })
        return false;
      })
    })
  </script>