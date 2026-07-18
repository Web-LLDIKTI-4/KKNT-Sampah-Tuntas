@extends('layouts.app')
@section('title','Laporan Akhir')
@section('container')

<x-page-header />

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
    $("#resultcontent").load("{{ url('pttugasakhir/listdata') }}");
})
</script>
@stop 