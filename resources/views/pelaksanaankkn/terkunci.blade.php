@extends('layouts.app')
@section('title','Dashboard')
@section('container')
@php($lockMessage = \App\Http\Middleware\RequirePelaksanaanKkn::MESSAGE)

<div class="d-flex mb-4 gap-4">
    <div class="avatar avatar-md">
        <div class="avatar-initial bg-label-primary rounded-4">
            <i class="ri-dashboard-3-fill ri-30px"></i>
        </div>
    </div>
    <div>
        <h5 class="mb-0">
            <span class="align-middle">Dashboard</span>
        </h5>
        <span>Akses sementara dibatasi</span>
    </div>
</div>

{{-- Skip duplicate flash: the card below already shows the lock message --}}
@if (session('error') !== $lockMessage)
    <x-alert />
@endif

<div class="card">
    <div class="card-body text-center py-6 px-4">
        <div class="avatar avatar-xl mx-auto mb-4">
            <div class="avatar-initial bg-label-warning rounded-circle">
                <i class="ri-lock-2-line ri-36px" aria-hidden="true"></i>
            </div>
        </div>
        <h5 class="mb-2">Waktu pelaksanaan KKN belum ditetapkan</h5>
        <div class="alert alert-warning d-inline-block text-start mb-5" role="status">
            {{ $lockMessage }}
        </div>
        <div>
            <a href="{{ url(Auth::user()->role === 'mahasiswa' ? 'mhsprofile' : 'profile') }}" class="btn btn-primary">
                <i class="ri-user-3-line me-2" aria-hidden="true"></i> Buka Profil Saya
            </a>
        </div>
    </div>
</div>
@endsection
