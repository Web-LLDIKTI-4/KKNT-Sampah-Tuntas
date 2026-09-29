@extends('layouts.app')
@section('title','Mahasiswa')
@section('container')
<x-crud-index :list-url="url('ptmahasiswa/listdata')" />
@stop
