@extends('layouts.app')
@section('title','Konversi Nilai Structure form')
@section('container')

<x-page-header title="Konversi Nilai" subtitle="Data Konversi Nilai" />

<div class="card">
    <div class="card-body">
        <p id="resultcontent">Loading data...</p>
    </div>
</div>
<script>
$(function(){
    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-lg');
    })
    $("#resultcontent").load("{{ url('admstructureform/listdata') }}");
})
</script>
@stop 