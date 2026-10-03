@extends('layouts.app')
@section('title','Log Bulanan Mahasiswa')
@section('container')

<x-page-header title="Log Bulanan Mahasiswa" subtitle="Data Log Bulanan" /> 

<div class="card">
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $('#modalku').on('show.bs.modal', function () {
            $(".modal-dialog").addClass("modal-lg");
        })
        $("#resultcontent").load("{{ url('admlogbulanan/listdatagroup') }}");
    })
</script>
@stop 