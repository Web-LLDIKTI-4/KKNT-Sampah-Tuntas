@extends('layouts.app')
@section('title','Log Harian')
@section('container')

<x-page-header /> 

<div class="card">
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('admlogkegiatan/listdatagroup') }}");
        $("#resultcontent").load("{{ url('admlogkegiatan/listdata') }}");
    })
</script>
@stop 