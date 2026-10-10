@extends('layouts.app')
@section('title','Dokumen')
@section('container')
<x-crud-index :list-url="url('panduan/listdata')" :add-url="url('panduan/tambah')" add-title="Tambah Dokumen" />
@stop
