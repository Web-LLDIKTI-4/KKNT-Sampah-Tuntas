@extends('layouts.app')
@section('title','Data Sampah Bulanan')
@section('container')
<x-crud-index
    title="Data Sampah Bulanan Kelurahan"
    :list-url="url('kpisampah/listdata')"
    :add-url="auth()->user()->akses === 'pjdesa' ? url('kpisampah/tambah') : null"
    modal-size="modal-xl" />
@include('kpisampah._hitung')
@stop
