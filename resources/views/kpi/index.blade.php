@extends('layouts.app')
@section('title','KPI')
@section('container')

<div class="d-flex mb-4 gap-4">
    <div class="avatar avatar-md">
        <div class="avatar-initial bg-label-primary rounded-4">
            <i class="ri-information-2-fill ri-30px"></i>
        </div>
    </div>
    <div>
        <h5 class="mb-0">
            <span class="align-middle">Key Performance Indicator (KPI)</span>
        </h5>
        <span>Data KPI</span>
    </div>
</div> 

<div class="card">
    <div class="card-header">
        <x-btn-modal url="{{ url('kpi/tambah') }}" title="Tambah Data"><i class="ri-add-circle-line me-1"></i>Tambah Data</x-btn-modal>
    </div>
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('kpi/listdata') }}");
    })
</script>
@stop 