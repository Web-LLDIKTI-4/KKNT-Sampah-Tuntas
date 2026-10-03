<form id="form-tambah" method="post" action="{{ url('logkegiatan/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')
    <x-form.input name="tanggal" type="date" required max="{{ date('Y-m-d') }}">
        <x-slot:label>Tanggal <span class="text-danger">*</span></x-slot:label>
        <span id="tanggal_error" class="text-danger"></span>
    </x-form.input>
    <div class="alert alert-solid-info d-flex align-items-center">
        <span class="alert-icon rounded">
            <i class="ri-error-warning-line ri-22px"></i>
        </span>
        Catatan: <br />
        Tidak diisikan gambar/dokumentasi kegitatan, hanya narasi atas kegiatan yang telah dilakukan.
    </div>
    <x-form.textarea name="deskripsi" input-class="form-control form-control-sm summernote">
        <x-slot:label>Deskripsi <span class="text-danger">*</span></x-slot:label>
        <p id="wordCount">Jumlah kata: 0</p>
        <span id="deskripsi_error" class="text-danger"></span>
    </x-form.textarea>
    <div class="row">
        <x-form.input name="volume" type="number" wrapper-class="form-group form-floating form-floating-outline mb-6 col" required min="0" step="any">
            <x-slot:label>Volume <span class="text-danger">*</span></x-slot:label>
            <span id="volume_error" class="text-danger"></span>
        </x-form.input>
        <x-form.input name="satuan" wrapper-class="form-group form-floating form-floating-outline mb-6 col" required maxlength="255">
            <x-slot:label>Satuan <span class="text-danger">*</span></x-slot:label>
            <span id="satuan_error" class="text-danger"></span>
        </x-form.input>
    </div>
    <x-form.select name="id_kpi" label="Nama KPI" input-class="form-control form-control-sm" :placeholder="false">
            @if($kpi)
                @foreach($kpi as $row)
                    <option value="{{ $row->id_kpi }}">{{ $row->nama_kpi }}</option>
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

    <x-form.input name="tautan" label="Tautan Dokumen" type="url" input-class="form-control" maxlength="255" placeholder="https://" />
    <x-button.save formId="form-tambah">
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