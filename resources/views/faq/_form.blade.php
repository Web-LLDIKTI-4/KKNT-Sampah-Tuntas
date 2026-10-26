@php
    $fieldPrefix = $faq ? 'faq-ubah-' : 'faq-tambah-';
    $answerTypes = [
        \App\Models\Faq::TYPE_TEXT => 'Deskripsi',
        \App\Models\Faq::TYPE_IMAGE => 'Gambar',
        \App\Models\Faq::TYPE_BOTH => 'Deskripsi + Gambar',
    ];
    $answerType = old('answer_type', $faq->answer_type ?? \App\Models\Faq::TYPE_TEXT);
    $answerType = array_key_exists($answerType, $answerTypes) ? $answerType : \App\Models\Faq::TYPE_TEXT;
    $currentImageUrl = $faq?->answer_image_url;
@endphp
<div class="row g-4" id="{{ $fieldPrefix }}answer" data-has-image="{{ $currentImageUrl ? '1' : '0' }}">
    <div class="col-12">
        <label class="form-label" for="{{ $fieldPrefix }}pertanyaan">Pertanyaan <span class="text-danger">*</span></label>
        <input type="text" id="{{ $fieldPrefix }}pertanyaan" name="pertanyaan" class="form-control" required maxlength="255"
            value="{{ $faq->pertanyaan ?? '' }}" placeholder="Contoh: Bagaimana cara mendaftar KKN Tematik?">
        <div class="form-text">Maksimal 255 karakter.</div>
    </div>
    <div class="col-12">
        <fieldset>
            <legend class="form-label fs-6 mb-2">Tipe Jawaban <span class="text-danger">*</span></legend>
            <div class="d-flex flex-wrap gap-2" role="radiogroup">
                @foreach ($answerTypes as $value => $label)
                    <input type="radio" class="btn-check" name="answer_type" value="{{ $value }}"
                        id="{{ $fieldPrefix }}answer_type-{{ $value }}" autocomplete="off" required @checked($answerType === $value)>
                    <label class="btn btn-outline-primary btn-sm" for="{{ $fieldPrefix }}answer_type-{{ $value }}">{{ $label }}</label>
                @endforeach
            </div>
            <span id="answer_type_error" class="text-danger d-block"></span>
        </fieldset>
    </div>
    <div class="col-12" data-answer-section="text" @if ($answerType === \App\Models\Faq::TYPE_IMAGE) hidden @endif>
        <label class="form-label" for="{{ $fieldPrefix }}jawaban">Jawaban <span class="text-danger">*</span></label>
        <textarea id="{{ $fieldPrefix }}jawaban" name="jawaban" class="form-control" rows="5" required maxlength="10000"
            @disabled($answerType === \App\Models\Faq::TYPE_IMAGE)>{{ old('jawaban', $faq->jawaban ?? '') }}</textarea>
        <div class="form-text">Teks biasa. Baris baru (Enter) tetap tampil sebagai baris baru di halaman depan.</div>
    </div>
    <div class="col-12" data-answer-section="image" @if ($answerType === \App\Models\Faq::TYPE_TEXT) hidden @endif>
        <label class="form-label" for="{{ $fieldPrefix }}answer_image">
            Gambar Jawaban
            @if ($currentImageUrl)
                <small class="text-body-secondary">(opsional, isi hanya untuk mengganti gambar)</small>
            @else
                <span class="text-danger">*</span>
            @endif
        </label>
        <figure class="mb-2 p-2 border rounded d-inline-block" data-image-preview @if (! $currentImageUrl) hidden @endif>
            <img @if ($currentImageUrl) src="{{ $currentImageUrl }}" @endif data-original-src="{{ $currentImageUrl }}" alt="Pratinjau gambar jawaban"
                class="d-block rounded img-fluid" style="max-height: 220px;">
            <figcaption class="small text-body-secondary mt-1" data-image-caption>Gambar saat ini</figcaption>
        </figure>
        <input type="file" id="{{ $fieldPrefix }}answer_image" name="answer_image" class="form-control"
            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-max-bytes="2097152"
            @required(! $currentImageUrl && $answerType !== \App\Models\Faq::TYPE_TEXT) @disabled($answerType === \App\Models\Faq::TYPE_TEXT)>
        <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
    </div>
</div>
<script>
$(function () {
    var $wrap = $('#{{ $fieldPrefix }}answer');
    var hasImage = $wrap.data('has-image') === 1;
    var $text = $wrap.find('[data-answer-section="text"]');
    var $image = $wrap.find('[data-answer-section="image"]');
    var $textarea = $text.find('textarea');
    var $file = $image.find('input[type="file"]');
    var $preview = $image.find('[data-image-preview]');
    var $img = $preview.find('img');
    var $caption = $preview.find('[data-image-caption]');
    var blobUrl = null;
    var typeText = @json(\App\Models\Faq::TYPE_TEXT);
    var typeImage = @json(\App\Models\Faq::TYPE_IMAGE);

    // Hidden inputs are disabled so they skip validation and are not submitted
    function syncSections() {
        var type = $wrap.find('[name="answer_type"]:checked').val() || typeText;
        var showText = type !== typeImage;
        var showImage = type !== typeText;
        $text.prop('hidden', !showText);
        $textarea.prop('disabled', !showText);
        $image.prop('hidden', !showImage);
        $file.prop('disabled', !showImage).prop('required', showImage && !hasImage);
    }

    function resetPreview() {
        if (blobUrl) {
            URL.revokeObjectURL(blobUrl);
            blobUrl = null;
        }
        var original = $img.data('original-src');
        original ? $img.attr('src', original) : $img.removeAttr('src');
        $caption.text('Gambar saat ini');
        $preview.prop('hidden', !original);
    }

    $wrap.on('change', '[name="answer_type"]', syncSections);

    $file.on('change', function () {
        var selectedFile = this.files[0];
        var allowed = ['image/jpeg', 'image/png', 'image/webp'];
        var message = '';
        if (selectedFile && allowed.indexOf(selectedFile.type) === -1) {
            message = 'Format gambar harus JPG, PNG, atau WEBP.';
        } else if (selectedFile && selectedFile.size > Number($(this).data('max-bytes'))) {
            message = 'Ukuran gambar melebihi batas 2 MB.';
        }
        this.setCustomValidity(message);
        resetPreview();
        if (message) {
            this.reportValidity();
            return;
        }
        if (selectedFile) {
            blobUrl = URL.createObjectURL(selectedFile);
            $img.attr('src', blobUrl);
            $caption.text('Pratinjau gambar baru');
            $preview.prop('hidden', false);
        }
    });

    syncSections();
});
</script>
