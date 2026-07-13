@extends('layouts/user')
@section('title','Home')
@section('container')
<div class="page-title">
    <div class="row justify-content-between align-items-center">
        <div class="col-md-6 d-flex align-items-center justify-content-between justify-content-md-start mb-3 mb-md-0">
            <!-- Page title + Go Back button -->
            <div class="d-inline-block">
                <h5 class="h4 d-inline-block font-weight-400 mb-0 text-white">Dashboard </h5>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
        <!-- Card body -->
        <div class="card-body">
        <div class="row">
            <div class="col">
            <h6 class="text-muted mb-1">Mahasiswa</h6>
            <span class="h3 font-weight-bold mb-0 ">{{ $jumlahmahasiswa }}</span>
            </div>
            <div class="col-auto">
            <div class="progress-circle progress-sm" id="progress-circle-1" data-progress="100" data-text="100%" data-color="info"></div>
            </div>
        </div>
        </div>
    </div>
    </div>
    <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
        <!-- Card body -->
        <div class="card-body">
        <div class="row">
            <div class="col">
            <h6 class="text-muted mb-1">Jumlah DPL</h6>
            <span class="h3 font-weight-bold mb-0 ">{{ $jumlahdpl }}</span>
            </div>
            <div class="col-auto">
            <div class="progress-circle progress-sm" id="progress-circle-2" data-progress="100" data-text="100%" data-color="dark"></div>
            </div>
        </div>
        </div>
    </div>
    </div>
    <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
        <!-- Card body -->
        <div class="card-body">
        <div class="row">
            <div class="col">
            <h6 class="text-muted mb-1">Log Kegiatan</h6>
            <a href="{{ url('logkegiatan') }}"><span class="h3 font-weight-bold mb-0 ">{{$jumlahlogkegiatan}}</span></a>
            </div>
            @php
                $persenkegiatan = round(($jumlahlogkegiatan/120)*100,1);
            @endphp
            <div class="col-auto">
            <div class="progress-circle progress-sm" id="progress-circle-3" data-progress="{{$persenkegiatan}}" data-text="{{$persenkegiatan}}%" data-color="danger" data-toggle="tooltip" data-placement="right" data-title="({{$jumlahlogkegiatan}}/120)*100"></div>
            </div>
        </div>
        </div>
    </div>
    </div>
    <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
        <!-- Card body -->
        <div class="card-body">
        <div class="row">
            <div class="col">
            <h6 class="text-muted mb-1">Log Bulanan</h6>
            <a href="{{ url('logbulanan') }}"><span class="h3 font-weight-bold mb-0 ">{{ $jumlahlogbulanan }}</span></a>
            </div>
            @php
                $persenbulan = round(($jumlahlogbulanan/4)*100,1);
            @endphp
            <div class="col-auto">
            <div class="progress-circle progress-sm" id="progress-circle-4" data-progress="{{ $persenbulan }}" data-text="{{$persenbulan}}%" data-color="success"  data-toggle="tooltip" data-placement="right" data-title="({{$jumlahlogbulanan}}/4)*100"></div>
            </div>
        </div>
        </div>
    </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-stats">
            <!-- Card body -->
            <div class="card-body">
            <div class="row">
                <div class="col">
                <h6 class="text-muted mb-1">PT Peserta</h6>
                <span class="h3 font-weight-bold mb-0 ">{{ $jumlahpt }}</span>
                </div>
                <div class="col-auto">
                <div class="progress-circle progress-sm" id="progress-circle-4" data-progress="100" data-text="100%" data-color="success"></div>
                </div>
            </div>
            </div>
        </div>
    </div>
   
</div>
<br>
<a class="btn btn-danger btn-block" href="{{ url('ptevaluasikegiatan') }}">Isi Evaluasi Kegiatan</a>
@stop 