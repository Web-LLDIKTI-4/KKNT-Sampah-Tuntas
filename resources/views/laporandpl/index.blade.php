@extends('layouts.app')
@section('title','Log Bulanan DPL')
@section('container')

<x-page-header /> 

<div class="card">
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('admlaporandpl/listdata') }}");
    })
</script>
@stop 