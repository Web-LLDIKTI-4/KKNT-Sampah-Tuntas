@extends('layouts.app')
@section('title','Kehadiran')
@section('container')

<x-page-header /> 

<div class="card">
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        loadPartial('#resultcontent', "{{ url('logkehadiran/listdata') }}");
    })
</script>
@stop 