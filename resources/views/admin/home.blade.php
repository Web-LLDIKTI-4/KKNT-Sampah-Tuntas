@extends('layouts/template')
@section('title','Home')
@section('container')
<div class="container-xxl flex-grow-1 container-p-y">
    
    <div class="row g-6">
    <!-- Organic Sessions Chart-->
    <div class="col-lg-4 col-md-6 order-1 order-lg-0">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-1">Info</h5>
                <p class="mb-0 card-subtitle">Kegiatan DPL</p>
            </div>
            <div class="card-body">
                @if($jumlahdpl != 0)
                <div class="d-flex align-items-center mb-6">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-info rounded">
                        <i class="ri-pencil-ruler-2-line ri-24px"></i>
                        </div>
                    </div>
                    <div class="ms-3 d-flex flex-column">
                        <h6 class="mb-1">DPL</h6>
                        <small>{{$jumlahdpl}}</small>
                    </div>
                </div>
                @endif                
                <div class="d-flex align-items-center mb-6">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-info rounded">
                        <i class="ri-pencil-ruler-2-line ri-24px"></i>
                        </div>
                    </div>
                    <div class="ms-3 d-flex flex-column">
                        @if(Auth::user()->role === "dpl")
                            @php
                                $persenjumlahlaporandpl = round(($jumlahlaporandpl/4)*100,1);
                            @endphp
                            <h6 class="mb-1">Laporan Anda</h6>
                            <small>{{$jumlahlaporandpl}} : {{$persenjumlahlaporandpl}} %</small>
                        @else
                            @php
                                if($jumlahlaporandpl > 0){
                                    $persenjumlahlaporandpl = round(($jumlahlaporandpl/($jumlahdpl*4))*100,1);
                                }else{
                                    $persenjumlahlaporandpl = 0;
                                }
                            @endphp
                            <h6 class="mb-1">Laporan DPL</h6>
                            <small>{{$jumlahlaporandpl}} : {{$persenjumlahlaporandpl}} %</small>
                        @endif  
                        
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-info rounded">
                        <i class="ri-pencil-ruler-2-line ri-24px"></i>
                        </div>
                    </div>
                    <div class="ms-3 d-flex flex-column">
                        <h6 class="mb-1">Konversi Nilai</h6>
                        @php
                        if($jumlahdplnilaikonversi == 0){
                            $persenjumlahdplnilaikonversi=0;
                        }else{
                            $persenjumlahdplnilaikonversi = round(($jumlahdplnilaikonversi/$jumlahdplmentoring)*100,1);
                        }
                        @endphp
                        <small>{{ $jumlahdplnilaikonversi }} : {{$persenjumlahdplnilaikonversi}} %</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--/ Organic Sessions Chart-->

    <!-- Project Timeline Chart-->
    <div class="col-lg-8 col-12">
        <div class="card h-100">
            <div class="row">
                <div class="col-md-8 col-12 order-2 order-md-0">
                <div class="card-header">
                    <h5 class="mb-1">Saran</h5>
                    <p class="mb-0 card-subtitle">Saran Pengunjung</p>
                </div>
                <div class="card-body">
                    <div id="saranpengunjung">
                        @if($saran->isEMpty())
                            -
                        @else
                            @foreach($saran as $item)
                                {{$item->nama}} : {{$item->saran}}<br>
                            @endforeach
                        @endif
                    </div>
                </div>
                </div>
                <div class="col-md-4 col-12 border-start">
                <div class="card-header">
                    <div class="d-flex justify-content-between">
                    <h5 class="mb-1">Informasi Data</h5>
                
                    <div class="dropdown">
                        <button
                        class="btn btn-text-secondary rounded-pill text-muted border-0 p-1"
                        type="button"
                        id="projectTimeline"
                        data-bs-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false">
                        <i class="ri-more-2-line ri-20px"></i>
                        </button>
                        <!--
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="projectTimeline">
                        <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                        <a class="dropdown-item" href="javascript:void(0);">Share</a>
                        <a class="dropdown-item" href="javascript:void(0);">Update</a>
                        </div>  
                        -->                 
                    </div>
                    
                    </div>
                    <p class="mb-0 card-subtitle">KKN Nusantara 2024</p>
                </div>
                <div class="card-body pt-4">
                    <div class="d-flex align-items-center mb-6">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded">
                            <i class="ri-smartphone-line ri-24px"></i>
                            </div>
                        </div>
                        <div class="ms-3 d-flex flex-column">
                            <h6 class="mb-1">PT Peserta</h6>
                            <small>{{ $jumlahpt }}</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-6">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded">
                            <i class="ri-sparkling-2-fill ri-24px"></i>
                            </div>
                        </div>
                        <div class="ms-3 d-flex flex-column">
                            <h6 class="mb-1">Mahasiswa</h6>
                            @php
                            if($jumlahmahasiswa > 0)
                                $persenjumlhmahasiswa = 100;
                            else
                                $persenjumlhmahasiswa = 0;
                            @endphp
                            <small>{{ $jumlahmahasiswa }} : {{$persenjumlhmahasiswa}} %</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-6">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-secondary rounded">
                            <i class="ri-bank-card-2-line ri-24px"></i>
                            </div>
                        </div>
                        <div class="ms-3 d-flex flex-column">
                            @php
                                if($jumlahlogbulanan == 0){
                                    $persenjumlahlogbulanan = 0;
                                }else{
                                    $persenjumlahlogbulanan = round(($jumlahlogbulanan/($jumlahmahasiswa*4))*100,1);
                                }
                            @endphp
                            <h6 class="mb-1">Log Bulanan Mahasiswa</h6>
                            <small>{{$jumlahlogbulanan}} : {{$persenjumlahlogbulanan}}%</small>
                        </div>  
                    </div>
                    @if($jumlahlogkegiatan)
                    <div class="d-flex align-items-center mb-6">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-secondary rounded">
                            <i class="ri-bank-card-2-line ri-24px"></i>
                            </div>
                        </div>
                        <div class="ms-3 d-flex flex-column">
                            <h6 class="mb-1">Log Kegiatan MHS</h6>
                            @php
                                $persenjumlahlogkegiatan = round(($jumlahlogkegiatan/($jumlahmahasiswa*120))*100,1);
                            @endphp
                            <small>{{$jumlahlogkegiatan}} : {{$persenjumlahlogkegiatan}}%</small>
                        </div>  
                    </div>                
                    @endif
                </div>
            </div>
        </div>
        </div>
    </div>
    <!--/ Project Timeline Chart-->
    </div>
</div>

@stop 