
<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="user_datatable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Hp</th>
                    <th>Perguruan Tinggi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
            <tfoot>
                <tr>
                    <th>No</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Hp</th>
                    <th>Perguruan Tinggi</th>
                    <th>Aksi</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#user_datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('mahasiswa.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'email', name: 'email'},
            {data: 'phone', name: 'phone'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        initComplete: function () {
            var table = this;
            this.api()
                .columns()
                .every(function (index) {
                    var column = this;
                    var title = column.footer().textContent;
    
                    // Create input element and add event listener
                    if (index !== 0 && index !== 6) { // Skip column "No" (index 0)
                        $('<input type="text" class="form-control form-control-sm p-1" placeholder="Search ' + title + '" />')
                            .appendTo($(column.footer()).empty())
                            .on('keyup change clear', function () {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    }
                });
        },
    });
    $("body").on("submit","[id^=hapusmhs-]",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();
        var dString = $(this).serialize();
        if(confirm("yakin data akan dihapus? semua dataterkaitakan di hapus!!")){
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
                    if(ret.success == true){		
                        var table = $('#user_datatable').DataTable(); // Menginisialisasi objek tabel
                        // Memuat ulang data tabel secara manual
                        table.ajax.reload();
                        toastr.success(ret.message)			
                    }else{
                        $.each(ret.errors, function(key, value) {
                            $("#" + key + "_error").html(value[0]); // Menampilkan pesan error di dalam field yang sesuai
                        });
                        toastr.warning(ret.message)	
                    }
                },
                error:function(xhr,ajaxOptions,thrownError){
                    console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
                }			
                
            })            
        }
        return false;
    });
  });
</script>