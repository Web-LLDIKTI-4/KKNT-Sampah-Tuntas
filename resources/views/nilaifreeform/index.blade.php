@extends('layouts.app')
@section('title','Data Free Form')
@section('container')

<div class="d-flex mb-4 gap-4">
    <div class="avatar avatar-md">
        <div class="avatar-initial bg-label-primary rounded-4">
            <i class="ri-information-2-fill ri-30px"></i>
        </div>
    </div>
    <div>
        <h5 class="mb-0">
            <span class="align-middle">Koversi Nilai Free form</span>
        </h5>
        <span>Data Free Form</span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        {{ $mahasiswa->nama }} | {{ $mahasiswa->nim }} | {{$mahasiswa->prodi}} | {{$mahasiswa->sp->nm_lemb}}
    </div>
    <div class="card-body">
        <h4>Data Nilai Free Form</h4>
        <p id="resultcontent_nilaikonversi">Loading data...</p>
    </div>
    <div class="card-body">
        <h4>Data Nilai Free Form</h4>
        <p id="resultcontent_freeform">Loading data...</p>
    </div>
</div>
<script>
$(function(){
    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-xl');
    })
    $("#resultcontent_nilaikonversi").load("{{ url('nilaifreeform/nilaikonversi') }}");
    $("#resultcontent_freeform").load("{{ url('nilaifreeform/freeform') }}");
})
</script>
@stop 