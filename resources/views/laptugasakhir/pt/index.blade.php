@extends('layouts.app')
@section('title','Laporan Akhir')
@section('container')
<div class="page-title">
    <div class="row justify-content-between align-items-center">
        <div class="col-md-6 d-flex align-items-center justify-content-between justify-content-md-start mb-3 mb-md-0">
            <!-- Page title + Go Back button -->
            <div class="d-inline-block">
                <h5 class="h4 d-inline-block font-weight-400 mb-0 text-white">Laporan Akhir</h5>
            </div>
        </div>
    </div>
</div>

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