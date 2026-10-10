@extends('layouts.app')
@section('title','Kategori Kegiatan')
@section('container')
<x-crud-index title="Kategori Kegiatan" :list-url="url('kategori-kegiatan/listdata')" :add-url="url('kategori-kegiatan/tambah')" />
@stop
