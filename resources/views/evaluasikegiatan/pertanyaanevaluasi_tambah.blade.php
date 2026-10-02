<form id="form-tambah" method="post" action="{{ url('admevaluasikegiatan/insert') }}" data-ajax-form>
    @csrf
    @method('PUT')    
    <div class="form-group form-floating form-floating-outline mb-6">       
        <textarea name="pertanyaan" class="form-control form-control-sm summernote"></textarea>
        <label>Pertanyaan</label>
    </div>
    <div id="wordCount" class="mb-3">Jumlah kata: 0</div>
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
            ],
            callbacks: {
                onKeyup: function() {
                    var text = $(this).summernote('code');
                    var wordCount = text.replace(/(<([^>]+)>)/ig,"").trim().split(/\s+/).length;
                    $('#wordCount').text("Jumlah kata: " + wordCount);
                },
            }
        });

        $
    });
</script>