@extends('layouts.app')
@section('title','Data Kehadiran Mahasiswa')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-body">
        <p id="resultcontent">loding data</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('admlogkehadiran/listdata') }}");
    })
</script>
@stop 