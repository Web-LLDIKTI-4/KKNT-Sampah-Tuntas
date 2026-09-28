@extends('layouts.app')
@section('title','KPI')
@section('container')
<x-crud-index title="Key Performance Indicator (KPI)" :list-url="url('kpi/listdata')" :add-url="url('kpi/tambah')" />
@stop
