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
    $("#resultcontent").load("{{ url('dpllaptugasakhir/listdata') }}");
    // Form nilai dirender server per baris: simpan otomatis saat nilai berubah lewat crud.js
    $("body").on("change", "[id^=form-nilai-]", function () {
        $(this).trigger("submit");
    })
})
</script>
@stop 