@php
    $fieldPrefix = $faq ? 'faq-ubah-' : 'faq-tambah-';
@endphp
<div class="row g-4">
    <div class="col-12">
        <label class="form-label" for="{{ $fieldPrefix }}pertanyaan">Pertanyaan <span class="text-danger">*</span></label>
        <input type="text" id="{{ $fieldPrefix }}pertanyaan" name="pertanyaan" class="form-control" required maxlength="255"
            value="{{ $faq->pertanyaan ?? '' }}" placeholder="Contoh: Bagaimana cara mendaftar KKN Tematik?">
        <div class="form-text">Maksimal 255 karakter.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $fieldPrefix }}jawaban">Jawaban <span class="text-danger">*</span></label>
        <textarea id="{{ $fieldPrefix }}jawaban" name="jawaban" class="form-control" rows="5" required maxlength="10000">{{ $faq->jawaban ?? '' }}</textarea>
        <div class="form-text">Teks biasa. Baris baru (Enter) tetap tampil sebagai baris baru di halaman depan.</div>
    </div>
</div>
