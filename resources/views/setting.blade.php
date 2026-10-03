@extends('layouts.app')
@section('title','Pengaturan Akun')
@section('container')
<x-page-header icon="ri-lock-line" title="Pengaturan Akun" subtitle="Kelola Kata Sandi Akun" />
<div class="card">
    <div class="card-body">
        <form method="post" id="form-update" action="{{ url('setting/update') }}" data-ajax-form>
            @csrf
            @method('PUT')
            <x-form.input name="plama" label="Masukkan Kata Sandi Lama" type="password" input-class="form-control" required autocomplete="current-password">
                <span id="plama_error" class="text-danger"></span>
            </x-form.input>
            <div class="row">
                <x-form.input name="pbaru" label="Masukkan Kata Sandi Baru" type="password" input-class="form-control" wrapper-class="form-group col form-floating form-floating-outline mb-6" required minlength="8" autocomplete="new-password">
                    <span id="pbaru_error" class="text-danger"></span>
                </x-form.input>
                <x-form.input name="pbaruulangi" label="Ulangi Kata Sandi Baru" type="password" input-class="form-control" wrapper-class="form-group col form-floating form-floating-outline mb-6" required minlength="8" autocomplete="new-password">
                    <span id="pbaruulangi_error" class="text-danger"></span>
                </x-form.input>
            </div>
            <hr>
            <x-button.save formId="form-update">Simpan</x-button.save>
    </form>
    </div>
</div>

@stop
