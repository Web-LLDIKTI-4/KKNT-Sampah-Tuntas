@extends('layouts.app')
@section('title','Evaluasi Kegiatan')
@section('container')

<x-page-header />

<div class="card">
    <div class="card-body">
        <p id="resultcontent">loading data...</p>
    </div>
</div>
<script>
$(function () {
    $('#resultcontent').load(@json(url('ptevaluasikegiatan/tambah')));
});
</script>
@stop
