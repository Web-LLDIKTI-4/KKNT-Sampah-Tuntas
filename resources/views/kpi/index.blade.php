@extends('layouts.app')
@section('title','Kelola Aktivitas')
@section('container')
<x-crud-index title="Kelola Aktivitas" :list-url="url('kpi/listdata')" :add-url="url('kpi/tambah')" />
@stop
