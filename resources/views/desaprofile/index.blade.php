@extends('layouts.app')
@section('title','Profil Desa / Kelurahan')
@section('container')
<x-crud-index :list-url="url('desaprofile/listdata')" :add-url="url('desaprofile/tambah')" />
@stop
