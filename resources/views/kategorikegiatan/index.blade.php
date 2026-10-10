@extends('layouts.app')
@section('title','Kelola Kategori Kegiatan')
@section('container')
<x-crud-index title="Kelola Kategori Kegiatan" :list-url="url('kategori-kegiatan/listdata')" :add-url="url('kategori-kegiatan/tambah')" />
@stop
