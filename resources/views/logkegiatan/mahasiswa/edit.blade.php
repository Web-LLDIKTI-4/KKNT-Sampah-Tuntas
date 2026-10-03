<form id="form-ubah" method="post" action="{{ url('logkegiatan/update') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="id_log" value="{{ $data->id_log }}">
    <x-form.input name="tanggal" label="Tanggal" type="date" :value="$data->tanggal" required max="{{ date('Y-m-d') }}">
        <span id="tanggal_error" class="text-danger"></span>
    </x-form.input>
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
        <x-form.input name="volume" label="Volume" type="number" :value="$data->volume" wrapper-class="form-group form-floating form-floating-outline mb-6 col" required min="0" step="any">
            <span id="volume_error" class="text-danger"></span>
        </x-form.input>
        <x-form.input name="satuan" label="Satuan" :value="$data->satuan" wrapper-class="form-group form-floating form-floating-outline mb-6 col" required maxlength="255">
            <span id="satuan_error" class="text-danger"></span>
        </x-form.input>
    </div>
    <x-form.select name="id_kpi" label="Nama KPI" input-class="form-control form-control-sm" :placeholder="false">
            @if($kpi)
                @foreach($kpi as $row)
                    <option value="{{ $row->id_kpi }}" @if($row->id_kpi == $data->id_kpi) selected @endif>{{ $row->nama_kpi }}</option>
                @endforeach
            @endif
        <x-slot:after>
        <span id="id_kpi_error" class="text-danger"></span>
        </x-slot:after>
    </x-form.select>
    <div class="alert alert-solid-info d-flex align-items-center">
        <span class="alert-icon rounded">
            <i class="ri-error-warning-line ri-22px"></i>
        </span>
        Catatan : <br />
        Dokumentasi tautan bisa dalam beluntuk tautan google drive atau media sosial.
    </div>
    <x-form.input name="tautan" label="Tautan Dokumen" type="url" :value="$data->tautan" input-class="form-control" maxlength="255" placeholder="https://" />
    <x-button.save formId="form-ubah">
        Simpan
    </x-button.save>
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