@extends('layouts.app')
@section('title','Kelola KPI')
@section('container')
<x-crud-index title="Kelola KPI" :list-url="url('kpi/listdata')" :add-url="url('kpi/tambah')" />
@stop
