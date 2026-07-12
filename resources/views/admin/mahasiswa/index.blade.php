@extends('layouts/template')
@section('title','Data Mahasiswa')
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
        <span>Daftar @yield('title')</span>
    </div>
</div>       

<div class="card">
    <div class="card-header">
        <a class="btn btn-sm btn-primary modalButton" href="#modalku" data-bs-toggle="modal" data-src="{{ url('mahasiswa/import') }}" title="Import Data"><i class="ri-chat-upload-fill me-1"></i> Import Data</a>
    </div>
    <div class="card-body">

        <!-- Tampilkan pesan sukses jika ada -->
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <!-- Tampilkan pesan kesalahan jika ada -->
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <p id="resultcontent">loding data</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('mahasiswa/listdata') }}");
    })
</script>
@stop 