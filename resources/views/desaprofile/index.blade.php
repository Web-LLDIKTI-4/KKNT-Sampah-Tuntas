@extends('layouts.app')
@section('title','Profil Kelurahan/Desa')
@section('container')
<x-crud-index :list-url="url('desaprofile/listdata')" :add-url="$canManage ? url('desaprofile/tambah') : null" />
@stop
