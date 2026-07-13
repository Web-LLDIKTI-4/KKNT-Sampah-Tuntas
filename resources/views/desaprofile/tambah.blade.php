<form id="form-tambah" method="post" action="{{ url('desaprofile/insert') }}">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="form-group col-md-4 form-floating form-floating-outline mb-6">
            <select name="tahun" class="form-control">
                @for($th=date('Y')-1; $th<=date('Y'); $th++)
                    <option value="{{$th}}" @if($th == date('Y')) selected @endif>{{$th}}</option>
                @endfor
            </select>
            <label>Tahun</label>
        </div>
        <div class="form-group col form-floating form-floating-outline mb-6">
            <select name="id_desa" class="form-control">
            @if($kecamatan)
                @foreach($kecamatan as $item)
                    <optgroup label="{{$item->kecamatan}}">
                        @foreach($item->desa as $row)
                            <option value="{{$row->id_desa}}" >{{$row->desa}}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            @endif
            </select>
            <label>Desa</label>
        </div>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="potensi" class="form-control form-control-sm summernote"></textarea>
        <label>Potensi</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="masalah" class="form-control form-control-sm summernote"></textarea>
        <label>Masalah</label>
    </div>

    <hr>
    <button type="submit" id="btnSubmit_form-tambah" class="btn btn-sm btn-primary"><i class="tf-icons ri-save-3-fill ri-16px me-1"></i> Simpan</button>
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
})
</script>