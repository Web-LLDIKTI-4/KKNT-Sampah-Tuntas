@php
    $fieldPrefix = $panduan ? 'panduan-ubah-' : 'panduan-tambah-';
    $isAktif = $panduan->is_aktif ?? true;
    $maxUploadLabel = \App\Support\FileSize::format($maxUploadBytes);
@endphp
<div class="row g-4">
    <div class="col-12">
        <label class="form-label" for="{{ $fieldPrefix }}judul">Judul <span class="text-danger">*</span></label>
        <input type="text" id="{{ $fieldPrefix }}judul" name="judul" class="form-control" required maxlength="200" value="{{ $panduan->judul ?? '' }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $fieldPrefix }}deskripsi">Deskripsi <small class="text-body-secondary">(opsional)</small></label>
        <textarea id="{{ $fieldPrefix }}deskripsi" name="deskripsi" class="form-control" rows="3" maxlength="2000">{{ $panduan->deskripsi ?? '' }}</textarea>
        <div class="form-text">Ringkasan isi panduan, maksimal 2000 karakter.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $fieldPrefix }}is_aktif">Status <span class="text-danger">*</span></label>
        <select id="{{ $fieldPrefix }}is_aktif" name="is_aktif" class="form-select" required aria-describedby="{{ $fieldPrefix }}is_aktif-hint">
            <option value="1" @selected($isAktif)>Aktif</option>
            <option value="0" @selected(! $isAktif)>Nonaktif</option>
        </select>
        <div class="form-text" id="{{ $fieldPrefix }}is_aktif-hint">Panduan <b>Aktif</b> tampil publik dan bisa diunduh siapa saja dari halaman login.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $fieldPrefix }}file">
            File Panduan
            @if ($panduan)
                <small class="text-body-secondary">(opsional, isi hanya untuk mengganti file)</small>
            @else
                <span class="text-danger">*</span>
            @endif
        </label>
        @if ($panduan)
            <div class="d-flex align-items-center gap-2 mb-2 p-2 border rounded">
                <i class="ri-file-text-line ri-22px text-primary"></i>
                <div class="text-truncate">
                    <a href="{{ route('panduan.download', $panduan->id_panduan) }}" class="fw-medium">{{ $panduan->nama_file }}</a>
                    <small class="d-block text-body-secondary">File saat ini &middot; {{ \App\Support\FileSize::format($panduan->ukuran) }}</small>
                </div>
            </div>
        @endif
        <input type="file" id="{{ $fieldPrefix }}file" name="file" class="form-control"
            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" data-max-bytes="{{ $maxUploadBytes }}" @required(! $panduan)>
        <div class="form-text">PDF, Word, Excel, atau PowerPoint. Maksimal {{ $maxUploadLabel }}.</div>
    </div>
</div>
<script>
$(function () {
    // Tolak file kebesaran sebelum dikirim agar tidak gagal di batas php.ini tanpa pesan jelas
    $('#{{ $fieldPrefix }}file').on('change', function () {
        var selectedFile = this.files[0];
        var maxBytes = Number($(this).data('max-bytes'));
        var isTooLarge = selectedFile && selectedFile.size > maxBytes;
        this.setCustomValidity(isTooLarge ? @json('Ukuran file melebihi batas '.$maxUploadLabel.'.') : '');
        if (isTooLarge) {
            this.reportValidity();
        }
    });
});
</script>
