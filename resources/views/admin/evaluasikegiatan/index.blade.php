@extends('layouts/template')
@section('title','Data Evaluasi Kegiatan')
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
<!-- Nav -->
 

<div class="btn-group mb-1 ms-4">
    <a href="{{ url('admevaluasikegiatan') }}" class="btn btn-secondary btn-sm waves-effect waves-light">
        <span class="d-none d-sm-block">Data Hasil Evaluasi Kegiatan</span><i class="ri-pause-fill d-sm-none"></i>
    </a>
    <a href="{{ url('admevaluasikegiatan/pertanyaanevaluasi') }}" class="btn btn-secondary btn-sm waves-effect waves-light">
        <span class="d-none d-sm-block">Pertanyaan Evaluasi Kegiatan</span><i class="ri-play-fill d-sm-none"></i>
    </a>
</div>
<div class="card">
    <div class="card-body">
        <p id="resultcontent">loding data</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('admevaluasikegiatan/hasilevaluasi') }}");
    })
</script>
@stop 