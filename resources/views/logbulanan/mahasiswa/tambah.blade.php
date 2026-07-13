<p>
  <button class="btn btn-primary btn-sm" type="button" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
    Data log bulanan bulan <b class="ms-1 me-1"> {{ Carbon\Carbon::create()->month($bulan)->format('F')}}</b> tahun <b class="ms-1 me-1"> {{ $tahun }}</b>
  </button>
</p>
<div class="collapse" id="collapseExample">
  <div class="card card-body">
    @if(!$logharian->isEMpty())
        <table class="table table-striped table-sm" id="tabel-data">
            <thead>
                <tr>
                    <th width="1%">No</th><th>Tanggal</th><th>Deskripsi</th><th>Link Dokumen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logharian as $row)
                    <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row->tanggal }}</td>
                    <td>{!! $row->deskripsi !!}</td>
                    <td><a href="{{ $row->tautan }}" target="_blank">{{ $row->tautan }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div>tidak ada log harian bulan <b>{{ Carbon\Carbon::create()->month($bulan)->format('F')}}</b> tahun <b>{{ $tahun }}</b> </div>
    @endif
  </div>
</div>
<form id="form-tambah" method="post" action="{{ url('logbulanan/insert') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="bulan" value="{{$bulan}}">
    <input type="hidden" name="tahun" value="{{$tahun}}">    
    <div class="alert alert-solid-info d-flex align-items-center">
    Panduan Pengisian :<br>
        1. Bagaimana aktifitas mentoring dan koordinasi dengan DPL maupun perangkat desa dan atau kecamatan ?<br>
        2. Apa yang telah kamu kerjakan dan bagaimana perkembangannya, apakah itu pekerjaan rutin atau yang berkaitan dengan KPI ?<br>
        3. Tantangan apa yang dihadapi selama di lokasi dan berikan alternatif solusi untuk menghadapainya ?<br>
        4. Apa saja dan jelaskan pengembangan kompetensi (hardskill maupun softskill) yang telah dicapai ?<br>
        minimal 200 kata (angka dan tanda baca tidak di hitung kata).
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea class="form-control summernote" name="deskripsi">
            @if($isi)
                {{ $isi->deskripsi }}
            @endif
        </textarea>
        <label>Log bulanan : Bulan <b>{{ Carbon\Carbon::create()->month($bulan)->format('F')}}</b> Tahun <b>{{ $tahun }}</b> </label>
        <p id="wordCount ps-5">Jumlah kata: 0</p>
        <span id="deskripsi_error" class="text-danger"></span>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">       
        <input type="text" class="form-control" name="tautan" value="{{ $isi->tautan ?? '' }}">
        <label>Tautan Dokumen (Keterangan : Tautan google drive yang berisikan dokumen laporan bulanan)</label>
    </div>
    <br>
    <x-btn-save formId="form-tambah"><i class="ri-save-2-fill pe-1"></i> Simpan</x-btn-save>
</form>
        
<script>
$(function(){
    let table = $('#tabel-data').DataTable({
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        serverSide: false,
        language: {
            "zeroRecords": "Tidak ada data yang ditemukan",
            "infoEmpty": "Tidak ada data yang tersedia",
            "sEmptyTable": "Tidak ada data yang tersedia di tabel"
        },
        columnDefs: [
            { targets: 'no-sort', orderable: false } // Tambahkan class 'no-sort' pada kolom 'Aksi'
        ]
    })
    $('.summernote').summernote({
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['misc', ['undo', 'redo']],
            // Anda tidak perlu menyertakan 'insert' di sini
        ],
        minHeight: 300, // Atur ketinggian minimum editor teks di sini (dalam piksel)
        // Opsi lainnya disini
        callbacks: {
            onKeyup: function() {
                var text = $(this).summernote('code');
                var plainText = text.replace(/(<([^>]+)>)/ig,"").trim(); // Menghapus tag HTML dari teks
                var wordArray = plainText.match(/\b[a-zA-Z]+\b/g); // Mencocokkan kata-kata (hanya huruf)
                var wordCount = wordArray ? wordArray.length : 0; // Menghitung panjang array kata atau 0 jika null
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
    $("#form-tambah").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();
        var dString = $(this).serialize();
        $("#tanggal_error").html('');
        $("#deskripsi_error").html('');
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
                if(ret.success == true){
                    toastr.success(ret.message)	
                    $("#listdata").load("{{ url('logbulanan/listdata') }}");		
			
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