@extends('layouts.app')
@section('title','Panduan')
@section('container')
<x-crud-index :list-url="url('panduan/listdata')" :add-url="url('panduan/tambah')" add-title="Tambah Panduan" />
@stop
