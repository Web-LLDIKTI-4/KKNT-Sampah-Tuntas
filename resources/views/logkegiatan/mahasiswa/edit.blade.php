<form id="form-ubah" method="post" action="{{ url('logkegiatan/update') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id_log" value="{{ $data->id_log }}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ $data->tanggal }}">
        <label>Tanggal</label>
        <span id="tanggal_error" class="text-danger"></span>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="deskripsi" class="form-control form-control-sm summernote">{{ $data->deskripsi }}</textarea>
        <p id="wordCount">Jumlah kata: 0</p>
        <label>Deskripsi</label>
        <span id="deskripsi_error" class="text-danger"></span>
    </div>
    <div class="row">
        <div class="form-group form-floating form-floating-outline mb-6 col">
            <input type="text" name="volume" class="form-control form-control-sm" value="{{ $data->volume }}">
            <label>Volume</label>
            <span id="volume_error" class="text-danger"></span>
        </div>
        <div class="form-group form-floating form-floating-outline mb-6 col">
            <input type="text" name="satuan" class="form-control form-control-sm" value="{{ $data->satuan }}">
            <label>Satuan</label>
            <span id="satuan_error" class="text-danger"></span>
        </div>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <select name="id_kpi" class="form-control form-control-sm">
            @if($kpi)
                @foreach($kpi as $row)
                    <option value="{{ $row->id_kpi }}" @if($row->id_kpi == $data->id_kpi) selected @endif>{{ $row->nama_kpi }}</option>
                @endforeach
            @endif
        </select>
        <label>Nama KPI</label>
        <span id="id_kpi_error" class="text-danger"></span>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="text" class="form-control" name="tautan" value="{{ $data->tautan }}">
        <label>Tautan Dokumen</label>
    </div>
    <x-btn-save formId="form-ubah">
        Simpan
    </x-btn-save>
</form>
<script>
$(function(){
    $('.summernote').summernote({
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['misc', ['undo', 'redo']],
            // Anda tidak perlu menyertakan 'insert' di sini
        ],
        callbacks: {
            onKeyup: function() {
                var text = $(this).summernote('code');
                var wordCount = text.replace(/(<([^>]+)>)/ig,"").trim().split(/\s+/).length;
                $('#wordCount').text("Jumlah kata: " + wordCount);
            },
            onPaste: function (e) {
                var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('text/html');
                e.preventDefault();
                var div = $('<div></div>');
                div.html(bufferText);
                div.find('*').removeAttr('style');
                setTimeout(function () {
                    document.execCommand('insertHtml', false, div.html());
                }, 10);
            }
        }
    });
    $("#form-ubah").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();
        var dString = $(this).serialize();
        $("#nama_kpi_error").html('');
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
                    var table = $('#dataTable').DataTable(); // Menginisialisasi objek tabel
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
            
        });
        return false;
    });
});

  </script>