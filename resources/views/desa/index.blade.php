@extends('layouts.app')
@section('title','Desa / Kelurahan')
@section('container')
<x-crud-index :list-url="url('desa/listdata')" :add-url="url('desa/tambah')" />
@stop
