@extends('layouts.app')
@section('title', 'Dasbor KPI')
@section('container')

<x-page-header
    icon="ri-line-chart-line"
    :title="$isPt ? 'Dasbor KPI Perguruan Tinggi' : 'Dasbor KPI'"
    subtitle="Rekap capaian KPI ketua kelompok. Kelompok yang belum mengisi dihitung 0%." />

<div class="card mb-6">
    <div class="card-body">
        <form id="kpi-filter" method="GET" action="{{ route('dashboardkpi') }}" class="row g-4 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="f-lokasi">Lokasi</label>
                <select name="lokasi" id="f-lokasi" class="form-select form-select-sm">
                    <option value="">Semua Lokasi</option>
                    @foreach ($lokasiList as $item)
                        <option value="{{ $item->id }}" @selected($filter['lokasi'] === $item->id)>{{ $item->nama_lokasi }}</option>
                    @endforeach
                </select>
            </div>
            @unless ($isPt)
                <div class="col-md-3">
                    <label class="form-label" for="f-pt">Perguruan Tinggi</label>
                    <select name="kodept" id="f-pt" class="form-select form-select-sm">
                        <option value="">Semua Perguruan Tinggi</option>
                        @foreach ($ptList as $item)
                            <option value="{{ $item->npsn }}" @selected($filter['kodept'] === $item->npsn)>{{ $item->nm_lemb }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <div class="col-md-4">
                <label class="form-label" for="f-kegiatan">Kegiatan</label>
                <select name="id_target" id="f-kegiatan" class="form-select form-select-sm">
                    <option value="">Semua Kegiatan</option>
                    @foreach ($kegiatanList as $item)
                        <option value="{{ $item->id_target }}" @selected($filter['id_target'] === $item->id_target)>
                            {{ $item->kpi->nama_kpi ?? '-' }} | {{ $item->kegiatan }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-center gap-2">
                <button type="button" id="kpi-reset" class="btn btn-sm btn-outline-secondary">Reset</button>
                <span id="kpi-loading" class="spinner-border spinner-border-sm text-primary" role="status" hidden></span>
            </div>
        </form>
    </div>
</div>

<div id="kpi-content">
    @include('kpidashboard._content')
</div>

<script>
$(function () {
    var form = $('#kpi-filter');
    var xhr = null;

    // Filter langsung merender ulang isi halaman tanpa reload
    function muat() {
        if (xhr) xhr.abort();
        var query = form.serialize();
        $('#kpi-loading').prop('hidden', false);
        xhr = $.ajax({
            url: form.attr('action'),
            data: query,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (html) {
                $('#kpi-content').html(html);
                history.replaceState(null, '', form.attr('action') + (query ? '?' + query : ''));
            },
            error: function (x) {
                if (x.statusText !== 'abort') toastr.error('Gagal memuat data, silakan coba lagi.');
            },
            complete: function () {
                $('#kpi-loading').prop('hidden', true);
            }
        });
    }

    form.on('change', 'select', muat);
    form.on('submit', function (e) { e.preventDefault(); muat(); });
    $('#kpi-reset').on('click', function () {
        form.find('select').val('').trigger('change.select2');
        muat();
    });
});
</script>
@stop
