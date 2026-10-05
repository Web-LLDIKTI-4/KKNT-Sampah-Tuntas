<p>
  <x-button data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
    Data log bulanan bulan <b class="ms-1 me-1"> {{ Carbon\Carbon::create((int) $tahun, (int) $bulan, 1)->translatedFormat('F')}}</b> tahun <b class="ms-1 me-1"> {{ $tahun }}</b>
  </x-button>
</p>

<div class="collapse" id="collapseExample">
  <div class="card card-body">
    @if(!$logharian->isEMpty())
        <table class="table table-striped table-sm" id="tabel-data">
            <thead>
                <tr>
                    <th width="1%">No</th><th>Tanggal</th><th>Kepala Keluarga</th><th>Alamat (RT/RW)</th><th>Memilah</th><th>Organik (kg)</th><th>Anorganik (kg)</th><th>Residu (kg)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logharian as $row)
                    <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row->tanggal }}</td>
                    <td>{{ $row->nama_kepala_keluarga }}</td>
                    <td>{{ $row->alamat_rumah }} (RT {{ $row->rt }}/RW {{ $row->rw }})</td>
                    <td class="text-center">{{ $row->memilah ? 'Ya' : 'Tidak' }}</td>
                    <td class="text-end">{{ number_format($row->organik_kg, 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($row->anorganik_kg, 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($row->residu_kg, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div>tidak ada log harian bulan <b>{{ Carbon\Carbon::create((int) $tahun, (int) $bulan, 1)->translatedFormat('F')}}</b> tahun <b>{{ $tahun }}</b> </div>
    @endif
  </div>
</div>
<form id="form-tambah" method="post" action="{{ url('logbulanan/insert') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="bulan" value="{{$bulan}}">
    <input type="hidden" name="tahun" value="{{$tahun}}">    
    <div class="alert alert-solid-info d-flex align-items-center">
    Panduan Pengisian : <br />
        1. Bagaimana aktifitas mentoring dan koordinasi dengan DPL maupun perangkat desa dan atau kecamatan ? <br />
        2. Apa yang telah dikerjakan dan bagaimana perkembangannya, apakah itu pekerjaan rutin atau yang berkaitan dengan KPI ? <br />
        3. Tantangan apa yang dihadapi selama di lokasi dan berikan alternatif solusi, dan bahkan tindaklanjutnya? <br />
        4. Apa saja dan jelaskan pengembangan kompetensi (hardskill maupun softskill) yang telah dicapai ? <br />
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea class="form-control summernote" name="deskripsi">
            @if($isi)
                {{ $isi->deskripsi }}
            @endif
        </textarea>
        <label>Deskripsi: </label>
        <p id="wordCount" class="ps-5">Jumlah kata: 0</p>
        <span id="deskripsi_error" class="text-danger"></span>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">       
        <input type="url" class="form-control" name="tautan" maxlength="2000" placeholder="https://" value="{{ $isi->tautan ?? '' }}">
        <label>Tautan Laporan</label>
    </div>
    <br>
    <x-button.save formId="form-tambah">
        Simpan
    </x-button.save>
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
        function updateWordCount(contents) {
        // Hilangkan tag HTML
        let plainText = $('<div>').html(contents).text().trim();

        // Hitung kata
        let words = plainText.match(/\S+/g);

        $('#wordCount').text('Jumlah kata: ' + (words ? words.length : 0));
    }

    function updateWordCount(contents) {
        // Hilangkan tag HTML
        let plainText = $('<div>').html(contents).text().trim();

        // Hitung kata
        let words = plainText.match(/\S+/g);

        $('#wordCount').text('Jumlah kata: ' + (words ? words.length : 0));
    }

    $('.summernote').summernote({
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['height', ['height']],
            ['misc', ['undo', 'redo']]
        ],
        minHeight: 300,

        callbacks: {
            onInit: function () {
                updateWordCount($(this).summernote('code'));
            },

            onChange: function (contents) {
                updateWordCount(contents);
            },

            onKeyup: function () {
                updateWordCount($(this).summernote('code'));
            },

            onPaste: function (e) {
                e.preventDefault();

                let clipboard = (e.originalEvent || e).clipboardData;
                let html = clipboard.getData('text/html');
                let text = clipboard.getData('text/plain');

                let div = $('<div>').html(html || text);
                div.find('*').removeAttr('style');

                document.execCommand('insertHTML', false, div.html());

                setTimeout(() => {
                    updateWordCount($(this).summernote('code'));
                }, 10);
            }
        }
    });
    $("#form-tambah").on("submit",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var dString = $(this).serialize();
        $("#tanggal_error").html('');
        $("#deskripsi_error").html('');
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