@extends('layouts.app')
@section('title','Lokasi Kegiatan KKN')
@section('container')
<x-crud-index :list-url="url('lokasiprogram/listdata')" :add-url="url('lokasiprogram/tambah')" />
@stop
