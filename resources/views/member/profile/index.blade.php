@extends('layouts/template')
@section('title','Data Profile')
@section('container')
<div class="d-flex mb-4 gap-4">
    <div class="avatar avatar-md">
        <div class="avatar-initial bg-label-primary rounded-4">
        <i class="ri-information-2-fill ri-30px"></i>
        </div>
    </div>
    <div>
        <h5 class="mb-0">
        <span class="align-middle">@yield('title')</span>
        </h5>
        <span>Data @yield('title')</span>
    </div>
</div> 

<p id="resultcontent">Loading data...</p>
<script>
$(function(){
    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-lg');
    })
    $("#resultcontent").load("{{ url('mhsprofile/data') }}");
})
</script>
@stop 