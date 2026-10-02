<div class="divider">
  <div class="divider-text"><h4><i class="ri-calendar-todo-line"></i> {{ date("Y-m-d") }}</h4></div>
</div>
<span id="tanggal_error" class="text-danger d-flex justify-content-center"></span>
<div class="d-flex justify-content-center">
    <div class="row">
        <div class="col ">
            <form id="form-datang" method="post" action="{{ url('logkehadiran/insert') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="mode" value="datang">
                <x-button.save formId="form-datang">Datang</x-button.save>
            </form>
        </div>
        <div class="col">
            <form id="form-pulang"  method="post" action="{{ url('logkehadiran/insert') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="mode" value="pulang">
                <x-button.save formId="form-pulang">Pulang</x-button.save>
            </form>
        </div>
    </div>
</div>
<script>
    $(function(){
      $("[id^=form-]").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var dString = $(this).serialize();
        $("#tanggal_error").html('');

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
            var table = $('#dataTable').DataTable(); // Menginisialisasi objek tabel
            // Memuat ulang data tabel secara manual
            table.ajax.reload();
            if(ret.success == true){		
              toastr.success(ret.message)		
              $("#modalku").modal("hide");
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