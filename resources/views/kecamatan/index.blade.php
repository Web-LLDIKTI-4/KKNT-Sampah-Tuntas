@extends('layouts.app')
@section('title','Kecamatan')
@section('container')
<x-crud-index :list-url="url('kecamatan/listdata')" :add-url="url('kecamatan/tambah')" />
@stop
