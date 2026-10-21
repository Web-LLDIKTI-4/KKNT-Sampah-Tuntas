@extends('layouts.app')
@section('title','Daftar Pelaksanaan KKN Perguruan Tinggi')
@section('container')
<x-crud-index :list-url="route('pelaksanaankkn.listdata')" />
@stop
