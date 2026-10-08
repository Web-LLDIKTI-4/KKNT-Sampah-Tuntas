@extends('layouts.app')
@section('title','Beranda')
@section('container')

<x-page-header title="Dashboard" description="Selamat datang di Dashboard."/>

<div class="flex-grow-1">
    @php
        $userRole = optional(Auth::user())->role;

        $jumlahdpl = (int) ($jumlahdpl ?? 0);
        $jumlahlaporandpl = (int) ($jumlahlaporandpl ?? 0);
        $jumlahdplmentoring = (int) ($jumlahdplmentoring ?? 0);
        $jumlahdplnilaikonversi = (int) ($jumlahdplnilaikonversi ?? 0);
        $jumlahmahasiswa = (int) ($jumlahmahasiswa ?? 0);
        $jumlahlogbulanan = (int) ($jumlahlogbulanan ?? 0);
        $jumlahlogkegiatan = (int) ($jumlahlogkegiatan ?? 0);

        $persenjumlahlaporandpl = 0;
        if ($userRole === 'dpl') {
            $persenjumlahlaporandpl = round(($jumlahlaporandpl / 4) * 100, 1);
        } else {
            $targetLaporanDpl = $jumlahdpl * 4;
            $persenjumlahlaporandpl = $targetLaporanDpl > 0 ? round(($jumlahlaporandpl / $targetLaporanDpl) * 100, 1) : 0;
        }

        $persenjumlahdplnilaikonversi = $jumlahdplmentoring > 0
            ? round(($jumlahdplnilaikonversi / $jumlahdplmentoring) * 100, 1)
            : 0;

        $persenjumlhmahasiswa = $jumlahmahasiswa > 0 ? 100 : 0;

        $targetLogBulanan = $jumlahmahasiswa * 4;
        $persenjumlahlogbulanan = $targetLogBulanan > 0
            ? round(($jumlahlogbulanan / $targetLogBulanan) * 100, 1)
            : 0;

        $targetLogKegiatan = $jumlahmahasiswa * 120;
        $persenjumlahlogkegiatan = $targetLogKegiatan > 0
            ? round(($jumlahlogkegiatan / $targetLogKegiatan) * 100, 1)
            : 0;
    @endphp
    
    <div class="row g-6">
    <!-- Organic Sessions Chart-->
    <div class="col-lg-4 col-md-6 order-1 order-lg-0">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-1">Informasi DPL</h5>
                <p class="mb-0 card-subtitle">Data DPL</p>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-6">
                    <div class="avatar">
                        <div class="avatar-initial bg-label-info rounded">
                        <i class="ri-pencil-ruler-2-line ri-24px"></i>
                        </div>
                    </div>
                    <div class="ms-3 d-flex flex-column">
                        <h6 class="mb-1">Jumlah DPL</h6>
                        <small>{{$jumlahdpl}} Orang</small>
                    </div>
                </div>               
                @if (!in_array($userRole, ['pt', 'kepala', 'pemda']))
                    <div class="d-flex align-items-center mb-6">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded">
                            <i class="ri-pencil-ruler-2-line ri-24px"></i>
                            </div>
                        </div>
                        <div class="ms-3 d-flex flex-column">
                            <h6 class="mb-1">Laporan DPL</h6>
                            <small>{{$jumlahlaporandpl}} Laporan</small>
                        </div>
                    </div>
                @endif
                
                @if (!in_array($userRole, ['pt', 'kepala', 'pemda']))
                    <div class="d-flex align-items-center">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded">
                            <i class="ri-pencil-ruler-2-line ri-24px"></i>
                            </div>
                        </div>
                        <div class="ms-3 d-flex flex-column">
                            <h6 class="mb-1">Konversi Nilai</h6>
                            <small>{{ $jumlahdplnilaikonversi }} Mahasiswa</small>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!--/ Organic Sessions Chart-->

    <!-- Project Timeline Chart-->
    <div class="col-lg-8 col-12">
        <div class="card h-100">
            <div class="row">
                {{-- <div class="col-md-8 col-12 order-2 order-md-0">
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
                </div> --}}
                <div class="col-md-4 col-12 border-start">
                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h5 class="mb-1">Informasi Mahasiswa</h5>
                    </div>
                    <p class="mb-0 card-subtitle">Data Mahasiswa</p>
                </div>
                <div class="card-body pt-4">
                    {{-- <div class="d-flex align-items-center mb-6">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded">
                            <i class="ri-smartphone-line ri-24px"></i>
                            </div>
                        </div>
                        <div class="ms-3 d-flex flex-column">
                            <h6 class="mb-1">Jumlah Pergururan Tinggi</h6>
                            <small>{{ $jumlahpt }} </small>
                        </div>
                    </div> --}}
                    <div class="d-flex align-items-center mb-6">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded">
                            <i class="ri-sparkling-2-fill ri-24px"></i>
                            </div>
                        </div>
                        <div class="ms-3 d-flex flex-column">
                            <h6 class="mb-1">Jumlah Mahasiswa</h6>
                            <small>{{ $jumlahmahasiswa }} Orang</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-6">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded">
                            <i class="ri-file-list-3-line ri-24px"></i>
                            </div>
                        </div>
                        <div class="ms-3 d-flex flex-column">
                            <h6 class="mb-1">Log Aktivitas Mahasiswa</h6>
                            <small>{{$jumlahlogkegiatan}} Kegiatan</small>
                        </div>  
                    </div>
                    <div class="d-flex align-items-center mb-6">
                        <div class="avatar">
                            <div class="avatar-initial bg-label-secondary rounded">
                            <i class="ri-bank-card-2-line ri-24px"></i>
                            </div>
                        </div>
                        <div class="ms-3 d-flex flex-column">
                            <h6 class="mb-1">Log Bulanan Mahasiswa</h6>
                            <small>{{$jumlahlogbulanan}} Laporan</small>
                        </div>  
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    <!--/ Project Timeline Chart-->
    </div>

    @isset($kpiHome)
        @include('kpidashboard._home')
    @endisset
</div>

@stop 