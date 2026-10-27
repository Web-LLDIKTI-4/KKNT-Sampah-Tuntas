@php
    $fieldPrefix = $realisasiKerja ? 'realisasikerja-ubah-' : 'realisasikerja-tambah-';
    $maxUploadLabel = \App\Support\FileSize::format($maxUploadBytes);
@endphp
<div class="row g-4">
    <div class="col-12">
        <label class="form-label" for="{{ $fieldPrefix }}judul">Judul <span class="text-danger">*</span></label>
        <input type="text" id="{{ $fieldPrefix }}judul" name="judul" class="form-control" required maxlength="200" value="{{ $realisasiKerja->judul ?? '' }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $fieldPrefix }}keterangan">Keterangan <small class="text-body-secondary">(opsional)</small></label>
        <textarea id="{{ $fieldPrefix }}keterangan" name="keterangan" class="form-control" rows="3" maxlength="2000">{{ $realisasiKerja->keterangan ?? '' }}</textarea>
        <div class="form-text">Maksimal 2000 karakter.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $fieldPrefix }}file">
            Dokumen Realisasi Kerja
            @if ($realisasiKerja)
                <small class="text-body-secondary">(opsional, isi hanya untuk mengganti file)</small>
            @else
                <span class="text-danger">*</span>
            @endif
        </label>
        @if ($realisasiKerja)
            <div class="d-flex align-items-center gap-2 mb-2 p-2 border rounded">
                <i class="ri-file-pdf-2-line ri-22px text-danger"></i>
                <div class="text-truncate">
                    <a href="{{ route('realisasikerja.view', $realisasiKerja->id_realisasi_kerja) }}" target="_blank" rel="noopener" class="fw-medium" title="Lihat PDF di tab baru">{{ $realisasiKerja->nama_file }}</a>
                    <small class="d-block text-body-secondary">File saat ini &middot; {{ \App\Support\FileSize::format($realisasiKerja->ukuran) }}</small>
                </div>
                <a href="{{ route('realisasikerja.view', $realisasiKerja->id_realisasi_kerja) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary ms-auto flex-shrink-0"><i class="ri-eye-line me-1"></i>Lihat</a>
            </div>
        @endif
        <input type="file" id="{{ $fieldPrefix }}file" name="file" class="form-control"
            accept=".pdf,application/pdf" data-max-bytes="{{ $maxUploadBytes }}" @required(! $realisasiKerja)>
        <div class="form-text">Hanya PDF. Maksimal {{ $maxUploadLabel }}.</div>
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
