<form id="form-ubah" method="post" action="{{ url('desaprofile/update') }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="id_profile" value="{{$data->id_profile}}">
    <div class="row">
        <div class="form-group col-md-4 form-floating form-floating-outline mb-6">
            <select name="tahun" class="form-control">
                @for($th=date('Y')-1; $th<=date('Y'); $th++)
                    <option value="{{$th}}" @if($th == $data->tahun) selected @endif>{{$th}}</option>
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
                            <option value="{{$row->id_desa}}" @if($row->id_desa == $data->id_desa) selected @endif>{{$row->desa}}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            @endif
            </select>
            <label>Desa</label>
        </div>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="potensi" class="form-control form-control-sm summernote">{{$data->potensi}}</textarea>
        <label>Potensi</label>
    </div>
    <div class="form-group form-floating form-floating-outline mb-6">
        <textarea name="masalah" class="form-control form-control-sm summernote">{{$data->masalah}}</textarea>
        <label>Masalah</label>
    </div>

    <hr>
    <button type="submit" id="btnSubmit_form-ubah" class="btn btn-sm btn-primary"><i class="tf-icons ri-save-3-fill ri-16px me-1"></i> Simpan</button>
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

    