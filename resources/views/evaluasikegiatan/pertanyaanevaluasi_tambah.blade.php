<form id="form-tambah" method="post" action="{{ url('admevaluasikegiatan/insert') }}">
    @csrf
    @method('PUT')    
    <div class="form-group form-floating form-floating-outline mb-6">       
        <textarea name="pertanyaan" class="form-control form-control-sm summernote"></textarea>
        <label>Pertanyaan</label>
    </div>
    <x-btn-save formId="form-tambah"><span class="tf-icons ri-save-3-fill ri-16px me-1"></span>Simpan</x-btn-save>
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
            /*
            onPaste: function (e) {
                var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('text/html');
                e.preventDefault();
                var div = $('<div></div>');
                div.html(bufferText);
                div.find('*').removeAttr('style');
                setTimeout(function () {
                    document.execCommand('insertHtml', false, div.html());
                }, 10);
            }*/
        }
    });
})
</script>