@extends('layouts.app')
@section('title','Konversi Nilai Free Form')
@section('container')

<div class="d-flex mb-4 gap-4">
    <div class="avatar avatar-md">
        <div class="avatar-initial bg-label-primary rounded-4">
            <i class="ri-information-2-fill ri-30px"></i>
        </div>
    </div>
    <div>
        <h5 class="mb-0">
            <span class="align-middle">Konversi Nilai Free Form</span>
        </h5>
        <span>Data Free Form</span>
    </div>
</div>

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
    $("#resultcontent").load("{{ url('admfreeform/listdata') }}");
})
</script>
@stop 