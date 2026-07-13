@extends('layouts.app')
@section('title','Data Kegiatan Mahasiswa')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-body">
        <p id="resultcontent">loding data</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('admlogkegiatan/listdata') }}");
    })
</script>
@stop 