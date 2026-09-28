<form id="form-ubah" method="post" action="{{ url('logkegiatan/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_log" value="{{ $data->id_log }}">
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="date" name="tanggal" class="form-control form-control-sm" required max="{{ date('Y-m-d') }}" value="{{ $data->tanggal }}">
        <label>Tanggal</label>
        <span id="tanggal_error" class="text-danger"></span>
    </div>
    <div class="alert alert-solid-info d-flex align-items-center">
        <span class="alert-icon rounded">
            <i class="ri-error-warning-line ri-22px"></i>
        </span>
        Catatan: <br />
        Tidak diisikan gambar/dokumentasi kegitatan, hanya narasi atas kegiatan yang telah dilakukan.
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="deskripsi" class="form-control form-control-sm summernote">{{ $data->deskripsi }}</textarea>
        <p id="wordCount">Jumlah kata: 0</p>
        <label>Deskripsi</label>
        <span id="deskripsi_error" class="text-danger"></span>
    </div>
    <div class="row">
        <div class="form-group form-floating form-floating-outline mb-6 col">
            <input type="number" name="volume" class="form-control form-control-sm" required min="0" step="any" value="{{ $data->volume }}">
            <label>Volume</label>
            <span id="volume_error" class="text-danger"></span>
        </div>
        <div class="form-group form-floating form-floating-outline mb-6 col">
            <input type="text" name="satuan" class="form-control form-control-sm" required maxlength="255" value="{{ $data->satuan }}">
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
    <div class="alert alert-solid-info d-flex align-items-center">
        <span class="alert-icon rounded">
            <i class="ri-error-warning-line ri-22px"></i>
        </span>
        Catatan : <br />
        Dokumentasi tautan bisa dalam beluntuk tautan google drive atau media sosial.
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <input type="url" class="form-control" name="tautan" maxlength="255" placeholder="https://" value="{{ $data->tautan }}">
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
});

  </script>