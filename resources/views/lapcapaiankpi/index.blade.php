@extends('layouts.app')
@section('title','Data Capaian key performance indicator')
@section('container')
<x-page-header /> 

<div class="card">
    <div class="card-body">
        <p id="resultcontent">loding data</p>
    </div>
</div>
<script>
$(function(){
    $('#modalku').on('show.bs.modal', function () {
        $(".modal-dialog").addClass("modal-lg");
    })
    $("#resultcontent").load("{{ url('lapcapaiankpi/listdata') }}");
})
</script>
@stop 