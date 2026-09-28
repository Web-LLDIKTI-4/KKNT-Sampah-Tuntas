@extends('layouts.app')
@section('title','Profile Desa / Kelurahan')
@section('container')
<x-crud-index :list-url="url('desaprofile/listdata')" :add-url="url('desaprofile/tambah')" />
@stop
