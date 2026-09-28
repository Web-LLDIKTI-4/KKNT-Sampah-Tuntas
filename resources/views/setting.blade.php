@extends('layouts.app')
@section('title','Setting Akun')
@section('container')
<x-page-header icon="ri-lock-line" title="Setting Akun" subtitle="Kelola Password Akun" />
<div class="card">
    <div class="card-body">
        <form method="post" id="form-update" action="{{ url('setting/update') }}" data-ajax-form>
            @csrf
            @method('PUT')
            <div class="form-group form-floating form-floating-outline mb-6">
                <input type="password" name="plama" class="form-control" required autocomplete="current-password">
                <label>Masukan Password Lama</label>
                <span id="plama_error" class="text-danger"></span>
            </div>
            <div class="row">
                <div class="form-group col form-floating form-floating-outline mb-6">
                    <input type="password" name="pbaru" class="form-control" required minlength="8" autocomplete="new-password">
                    <label>Masukan Password Baru</label>
                    <span id="pbaru_error" class="text-danger"></span>
                </div>
                <div class="form-group col form-floating form-floating-outline mb-6">
                    <input type="password" name="pbaruulangi" class="form-control" required minlength="8" autocomplete="new-password">
                    <label>Ulangi Password Baru</label>
                    <span id="pbaruulangi_error" class="text-danger"></span>
                </div>
            </div>
            <hr>
            <x-btn-save formId="form-update">Simpan</x-btn-save>
    </form>
    </div>
</div>

@stop
