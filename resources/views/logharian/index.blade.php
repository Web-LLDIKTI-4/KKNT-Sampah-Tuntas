@extends('layouts.app')
@section('title','Log Aktivitas Mahasiswa')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('admlogharian/listdata') }}");
    })
</script>
@stop 