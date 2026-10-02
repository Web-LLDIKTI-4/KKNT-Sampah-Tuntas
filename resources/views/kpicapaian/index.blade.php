@extends('layouts.app')
@section('title','Capaian KPI')
@section('container')
<x-crud-index
    title="Capaian Key Performance Indicator (KPI)"
    :list-url="url('kpicapaian/listdata')"
    :add-url="auth()->user()->akses === 'pjdesa' ? url('kpicapaian/tambah') : null" />
@stop
