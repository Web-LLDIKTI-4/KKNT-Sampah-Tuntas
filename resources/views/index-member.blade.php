@extends('layouts.app')
@section('title','Beranda')
@section('container')
<x-page-header /> 

<div class="flex-grow-1 container-p-y">
    <!-- Product List Widget -->
    <div class="card mb-6">
        <div class="card-widget-separator-wrapper">
            <div class="card-body card-widget-separator">
            <div class="row gy-4 gy-sm-1">
                <div class="col-sm-6 col-lg-3">
                <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                    <div>
                    <p class="mb-1">Mahasiswa</p>
                    <h4 class="mb-1">{{ $jumlahmahasiswa }}</h4>
                    <p class="mb-0">
                        <span class="badge rounded-pill bg-label-success">100%</span>
                    </p>
                    </div>
                    <div class="avatar me-sm-6">
                    <span class="avatar-initial rounded text-heading">
                        <i class="ri-home-6-line ri-26px"></i>
                    </span>
                    </div>
                </div>
                <hr class="d-none d-sm-block d-lg-none me-6">
                </div>
                <div class="col-sm-6 col-lg-3">
                <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                    <div>
                    <p class="mb-1">Jumlah DPL</p>
                    <h4 class="mb-1">{{ $jumlahdpl }}</h4>
                    <p class="mb-0">
                        <span class="badge rounded-pill bg-label-success">100%</span>
                    </p>
                    </div>
                    <div class="avatar me-lg-6">
                    <span class="avatar-initial rounded text-heading">
                        <i class="ri-computer-line ri-26px"></i>
                    </span>
                    </div>
                </div>
                <hr class="d-none d-sm-block d-lg-none">
                </div>
                <div class="col-sm-6 col-lg-3">
                <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                    <div>
                    <p class="mb-1">Log Kegiatan</p>
                    <h4 class="mb-1">{{$jumlahlogkegiatan}}</h4>
                    {{-- @php
                        $persenkegiatan = round(($jumlahlogkegiatan/120)*100,1);
                    @endphp
                    <p class="mb-0">
                        <span class="me-2">({{$jumlahlogkegiatan}}/120)*100</span><span class="badge rounded-pill bg-label-success">{{$persenkegiatan}}%</span>
                    </p> --}}
                    </div>
                    <div class="avatar me-sm-6">
                    <span class="avatar-initial rounded text-heading">
                        <i class="ri-gift-line ri-26px"></i>
                    </span>
                    </div>
                </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                    <p class="mb-1">Log Bulanan</p>
                    <h4 class="mb-1">{{ $jumlahlogbulanan }}</h4>
                    {{-- @php
                        $persenbulan = round(($jumlahlogbulanan/4)*100,1);
                    @endphp
                    <p class="mb-0">
                        <span class="me-2">({{$jumlahlogbulanan}}/4)*100</span><span class="badge rounded-pill bg-label-danger">{{$persenbulan}}%</span>
                    </p> --}}
                    </div>
                    <div class="avatar">
                    <span class="avatar-initial rounded text-heading">
                        <i class="ri-money-dollar-circle-line ri-26px"></i>
                    </span>
                    </div>
                </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    <!-- Product List Widget -->
    <div class="card mb-6">
        <div class="card-widget-separator-wrapper">
            <div class="card-body card-widget-separator">
            <div class="row gy-4 gy-sm-1">
                <div class="col">
                <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                    <div>
                    <p class="mb-1">Log Bulanan</p>
                    <h4 class="mb-1">{{ $jumlahlogbulanan }}</h4>
                    @php
                        $persenbulan = round(($jumlahlogbulanan/4)*100,1);
                    @endphp
                    <p class="mb-0">
                        <span class="me-2">({{$jumlahlogbulanan}}/4)*100</span><span class="badge rounded-pill bg-label-success">{{ $persenbulan }}%</span>
                    </p>
                    </div>
                    <div class="avatar me-sm-6">
                    <span class="avatar-initial rounded text-heading">
                        <i class="ri-home-6-line ri-26px"></i>
                    </span>
                    </div>
                </div>
                <hr class="d-none d-sm-block d-lg-none me-6">
                </div>
                <div class="col">
                <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                    <div>
                    <p class="mb-1">Perguruan Tinggi Peserta</p>
                    <h4 class="mb-1">{{ $jumlahpt }}</h4>
                    <p class="mb-0">
                        <span class="me-2">21k orders</span><span class="badge rounded-pill bg-label-success">100%</span>
                    </p>
                    </div>
                    <div class="avatar me-lg-6">
                    <span class="avatar-initial rounded text-heading">
                        <i class="ri-computer-line ri-26px"></i>
                    </span>
                    </div>
                </div>
                <hr class="d-none d-sm-block d-lg-none">
                </div>
                @if(Auth::user()->akses === "pjdesa")
                <div class="col">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                        <p class="mb-1">Capaian KPI</p>
                        <h4 class="mb-1">{{ $jumlahcapaiankpi }}</h4>
                        {{-- @php
                            if($jumlahcapaiankpi == 0){
                                $persenjumlahcapaiankpi = 0;
                            }else{
                                $persenjumlahcapaiankpi = round(($jumlahcapaiankpi/5)*100,1);
                            }
                        @endphp
                        <p class="mb-0">
                            <span class="me-2">({{$jumlahcapaiankpi}})/5*100</span><span class="badge rounded-pill bg-label-danger">{{$persenjumlahcapaiankpi}}%</span>
                        </p> --}}
                        </div>
                        <div class="avatar">
                        <span class="avatar-initial rounded text-heading">
                            <i class="ri-money-dollar-circle-line ri-26px"></i>
                        </span>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            </div>
        </div>
    </div>
</div>
@stop 