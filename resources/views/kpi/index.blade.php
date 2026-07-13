@extends('layouts.app')
@section('title','Data key performance indicator')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-header">
        <a class="btn btn-sm btn-primary modalButton" href="#modalku" data-bs-toggle="modal" data-src="{{ url('kpi/tambah') }}" title="Tambah Data"><i class="ri-add-circle-line me-1"></i>Tambah Data</a>
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