@php
    $pelaksanaan = $pelaksanaanKkn ?? null;
    $bisaIsi = (bool) ($bisaIsiPelaksanaan ?? false);
    $tglMin = \App\Http\Requests\Pt\PelaksanaanKknRequest::MIN_TANGGAL;
    $maxDurasi = \App\Http\Requests\Pt\PelaksanaanKknRequest::MAX_DURASI_HARI;
    $tglMax = (now()->year + 1) . '-12-31';
@endphp
<div class="card mb-6" id="cardPelaksanaanKkn">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h5 class="mb-1">Pelaksanaan KKN</h5>
            <p class="mb-0 card-subtitle">Rentang tanggal pelaksanaan KKN di perguruan tinggi Anda.</p>
        </div>
        @if ($pelaksanaan)
            <span class="badge rounded-pill bg-label-success">Tersimpan</span>
        @else
            <span class="badge rounded-pill bg-label-warning">Belum diisi</span>
        @endif
    </div>
    <div class="card-body">
        @unless ($bisaIsi)
            <div class="alert alert-warning mb-4" role="alert">
                Akun PT belum terhubung ke data perguruan tinggi, jadi pelaksanaan KKN belum bisa diisi. Hubungi admin.
            </div>
        @endunless

        <form id="formPelaksanaanKkn" data-ajax-form data-reload-page action="{{ route('pelaksanaankkn.simpan') }}" method="POST">
            @csrf
            @method('PUT')
            <fieldset @disabled(! $bisaIsi)>
                <div class="row g-4 align-items-start">
                    <div class="col-sm-6 col-lg-4">
                        <label for="pk_tanggal_mulai" class="form-label">Tanggal mulai</label>
                        <input type="date" id="pk_tanggal_mulai" name="tanggal_mulai" class="form-control" required
                            min="{{ $tglMin }}" max="{{ $tglMax }}"
                            value="{{ $pelaksanaan?->tanggal_mulai?->format('Y-m-d') }}">
                        <span id="tanggal_mulai_error" class="text-danger small d-block"></span>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <label for="pk_tanggal_selesai" class="form-label">Tanggal selesai</label>
                        <input type="date" id="pk_tanggal_selesai" name="tanggal_selesai" class="form-control" required
                            min="{{ $pelaksanaan?->tanggal_mulai?->format('Y-m-d') ?? $tglMin }}" max="{{ $tglMax }}"
                            value="{{ $pelaksanaan?->tanggal_selesai?->format('Y-m-d') }}">
                        <span id="tanggal_selesai_error" class="text-danger small d-block"></span>
                    </div>
                    <div class="col-lg-4">
                        <span class="form-label d-block">Durasi</span>
                        <div class="d-flex align-items-baseline gap-2 px-4 py-2 rounded bg-label-primary">
                            <span class="h4 mb-0 text-primary" id="pkDurasi" aria-live="polite">-</span>
                            <small class="text-body" id="pkDurasiNote">pilih kedua tanggal</small>
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
        @if ($pelaksanaan && $bisaIsi)
            <form id="formHapusPelaksanaanKkn" data-ajax-form data-reload-page action="{{ route('pelaksanaankkn.destroy') }}" method="POST" class="d-none">
                @csrf
                @method('PUT')
            </form>
        @endif

        <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
            @if ($bisaIsi)
                <button type="submit" form="formPelaksanaanKkn" id="btnSubmit_formPelaksanaanKkn" class="btn btn-primary">
                    <i class="ri-save-line me-1"></i> {{ $pelaksanaan ? 'Perbarui' : 'Simpan' }}
                </button>
                @if ($pelaksanaan)
                    <button type="submit" form="formHapusPelaksanaanKkn" id="btnSubmit_formHapusPelaksanaanKkn" class="btn btn-outline-danger">
                        <i class="ri-delete-bin-line me-1"></i> Hapus
                    </button>
                @endif
            @endif
            @if ($pelaksanaan?->updated_at)
                <small class="text-muted ms-sm-auto">Terakhir diperbarui {{ $pelaksanaan->updated_at->translatedFormat('d M Y H:i') }}</small>
            @endif
        </div>
    </div>
</div>
<script>
$(function () {
    var $mulai = $('#pk_tanggal_mulai');
    var $selesai = $('#pk_tanggal_selesai');
    var $durasi = $('#pkDurasi');
    var $note = $('#pkDurasiNote');

    function toUtc(value) {
        var p = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
        return p ? Date.UTC(+p[1], +p[2] - 1, +p[3]) : null;
    }

    // Preview durasi inklusif (1-3 Jan = 3 hari); validasi final tetap di server
    function renderDurasi() {
        var a = toUtc($mulai.val());
        var b = toUtc($selesai.val());
        $selesai.attr('min', $mulai.val() || @json($tglMin));
        $note.removeClass('text-danger').addClass('text-body');
        if (a === null || b === null) {
            $durasi.text('-');
            $note.text('pilih kedua tanggal');
            return;
        }
        var days = Math.round((b - a) / 86400000) + 1;
        if (days < 1) {
            $durasi.text('-');
            $note.text('tanggal selesai sebelum tanggal mulai').removeClass('text-body').addClass('text-danger');
            return;
        }
        $durasi.text(days);
        var maxDurasi = @json($maxDurasi);
        $note.text(days > maxDurasi ? 'hari (maksimal ' + maxDurasi + ' hari)' : 'hari');
        if (days > maxDurasi) {
            $note.removeClass('text-body').addClass('text-danger');
        }
    }

    $mulai.add($selesai).on('input change', renderDurasi);
    renderDurasi();

    // Konfirmasi sebelum hapus; handler langsung di form jalan sebelum handler delegasi crud.js
    $('#formHapusPelaksanaanKkn').on('submit', function (e) {
        if (!confirm('Hapus data pelaksanaan KKN? Tindakan ini tidak bisa dibatalkan.')) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });
});
</script>
