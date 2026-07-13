@extends('layouts.app')
@section('title','Data key performance indicator')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-header">
        <x-btn-modal url="{{ url('kpi/tambah') }}" title="Tambah Data"><i class="ri-add-circle-line me-1"></i>Tambah Data</x-btn-modal>
    </div>
    <div class="card-body">
        <p id="resultcontent">loding data</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('kpi/listdata') }}");
    })
</script>
@stop 