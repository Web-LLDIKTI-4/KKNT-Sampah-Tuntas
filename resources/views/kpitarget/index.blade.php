@extends('layouts.app')
@section('title','Target Key Performance Indicator (KPI)')
@section('container')
<x-crud-index :list-url="url('kpitarget/listdata')" :add-url="url('kpitarget/tambah')" />
@stop
