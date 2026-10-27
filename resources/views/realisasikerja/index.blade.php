@extends('layouts.app')
@section('title','Realisasi Kerja')
@section('container')
<x-crud-index :list-url="route('realisasikerja.listdata')" :add-url="$canCreate ? route('realisasikerja.tambah') : null" add-title="Tambah Realisasi Kerja" />
@stop
