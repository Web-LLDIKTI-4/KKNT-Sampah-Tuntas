@extends('layouts.app')
@section('title','Ketua Kelompok')
@section('container')
<x-crud-index :list-url="url('pjdesa/listdata')" :add-url="url('pjdesa/tambah')" />
@stop
