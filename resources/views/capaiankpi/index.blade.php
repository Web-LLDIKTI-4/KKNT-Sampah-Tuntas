@extends('layouts.app')
@section('title', 'Capaian KPI Kelurahan/Desa')
@section('container')

<x-page-header
    icon="ri-bar-chart-box-line"
    subtitle="Ketaatan pemilahan dan penurunan timbulan sampah per kelurahan/desa, beserta perguruan tinggi yang ditempatkan." />

<div class="card">
    <div class="card-body">
        <div data-filter-host="{{ route('capaian-kpi') }}">
            @include('capaiankpi._tabel')
        </div>
    </div>
</div>

<script src="{{ asset('js/drilldown.js') }}?v={{ filemtime(public_path('js/drilldown.js')) }}"></script>
<script>
$(function () {
    // Modal detail PTS lebar (tabel 6–7 kolom); namespaced agar handler terdaftar sekali
    $('#modalku').off('show.bs.modal.capaiankpi').on('show.bs.modal.capaiankpi', function () {
        $(this).find('.modal-dialog').addClass('modal-xl');
    });
});
</script>
@stop
