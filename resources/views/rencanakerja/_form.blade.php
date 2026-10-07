@php
    $fieldPrefix = $rencanaKerja ? 'rencanakerja-ubah-' : 'rencanakerja-tambah-';
    $maxUploadLabel = \App\Support\FileSize::format($maxUploadBytes);
    $tahunMax = (int) date('Y') + 1;
    $tahunTerpilih = (int) ($rencanaKerja->tahun ?? date('Y'));
@endphp
<div class="row g-4">
    <div class="col-md-8">
        <label class="form-label" for="{{ $fieldPrefix }}judul">Judul <span class="text-danger">*</span></label>
        <input type="text" id="{{ $fieldPrefix }}judul" name="judul" class="form-control" required maxlength="200" value="{{ $rencanaKerja->judul ?? '' }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $fieldPrefix }}tahun">Tahun <span class="text-danger">*</span></label>
        <select id="{{ $fieldPrefix }}tahun" name="tahun" class="form-select" required>
            @for ($tahun = $tahunMax; $tahun >= 2020; $tahun--)
                <option value="{{ $tahun }}" @selected($tahun === $tahunTerpilih)>{{ $tahun }}</option>
            @endfor
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $fieldPrefix }}keterangan">Keterangan <small class="text-body-secondary">(opsional)</small></label>
        <textarea id="{{ $fieldPrefix }}keterangan" name="keterangan" class="form-control" rows="3" maxlength="2000">{{ $rencanaKerja->keterangan ?? '' }}</textarea>
        <div class="form-text">Maksimal 2000 karakter.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $fieldPrefix }}file">
            Dokumen Rencana Kerja
            @if ($rencanaKerja)
                <small class="text-body-secondary">(opsional, isi hanya untuk mengganti file)</small>
            @else
                <span class="text-danger">*</span>
            @endif
        </label>
        @if ($rencanaKerja)
            <div class="d-flex align-items-center gap-2 mb-2 p-2 border rounded">
                <i class="ri-file-text-line ri-22px text-primary"></i>
                <div class="text-truncate">
                    <a href="{{ route('rencanakerja.download', $rencanaKerja->id_rencana_kerja) }}" class="fw-medium">{{ $rencanaKerja->nama_file }}</a>
                    <small class="d-block text-body-secondary">File saat ini &middot; {{ \App\Support\FileSize::format($rencanaKerja->ukuran) }}</small>
                </div>
            </div>
        @endif
        <input type="file" id="{{ $fieldPrefix }}file" name="file" class="form-control"
            accept=".pdf,.doc,.docx,.xls,.xlsx" data-max-bytes="{{ $maxUploadBytes }}" @required(! $rencanaKerja)>
        <div class="form-text">PDF, Word, atau Excel. Maksimal {{ $maxUploadLabel }}.</div>
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
