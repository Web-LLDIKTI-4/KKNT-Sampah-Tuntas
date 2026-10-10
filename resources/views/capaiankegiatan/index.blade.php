@extends('layouts.app')
@section('title','Capaian Kegiatan')
@section('container')
@php
    use App\Models\PenguranganSampah;
    $berat = fn ($v) => PenguranganSampah::formatAngka($v, 2);
@endphp

<x-page-header
    icon="ri-line-chart-line"
    title="Capaian Kegiatan"
    :subtitle="$desa ? 'Rekap sampah Kel. '.$desa->desa.', Kec. '.($desa->kecamatan?->kecamatan ?? '-').' dari Pendataan Sampah Penduduk.' : 'Rekap sampah dari Pendataan Sampah Penduduk.'" />

{{-- Read-only: rekap dihitung otomatis dari Pendataan Sampah Penduduk --}}
<div class="card mb-6">
    <div class="card-body">
        @if ($desa)
            <div class="d-flex flex-wrap gap-5 mb-4">
                <div>
                    <div class="small text-muted">Persentase Penurunan Sampah</div>
                    <x-status-pengurangan :persen="$total->persen_penurunan" class="fs-6" />
                </div>
                <div>
                    <div class="small text-muted">Ketaatan Pemilahan</div>
                    <div class="fw-medium">{{ PenguranganSampah::formatPersen($total->persen_ketaatan) }}</div>
                </div>
                <div>
                    <div class="small text-muted">Total Sampah Dihasilkan</div>
                    <div class="fw-medium">{{ $berat($total->total_dihasilkan) }} kg</div>
                </div>
                <div>
                    <div class="small text-muted">Total Sampah Terkelola</div>
                    <div class="fw-medium">{{ $berat($total->total_terkelola) }} kg</div>
                </div>
            </div>
            @include('rekapsampah._lldikti', ['rekap' => $rekap])
            <p class="small text-muted mt-3 mb-0">Tambah atau perbaiki data lewat menu <a href="{{ url('pendataanpemilahan') }}">Pendataan Sampah Penduduk</a>.</p>
        @else
            <div class="alert alert-warning mb-0">Anda belum terdaftar di lokasi KKN (kelurahan), sehingga rekap belum dapat ditampilkan.</div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Riwayat Capaian Kegiatan</h5>
        {{-- Input capaian hanya ketua kelompok --}}
        @if ($isKetua ?? false)
            <x-button :modal="url('capaiankegiatan/tambah')" title="Tambah Data" icon="ri-add-circle-line">Tambah Data</x-button>
        @endif
    </div>
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function () {
        $('#modalku').on('show.bs.modal', function () {
            $(this).find('.modal-dialog').removeClass('modal-sm modal-lg modal-xl').addClass('modal-xl');
        });
        $("#resultcontent").load("{{ url('capaiankegiatan/listdata') }}");
    });
</script>
@stop
