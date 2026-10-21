@extends('layouts.app')
@section('title','Waktu Pelaksanan KKN PTS')
@section('container')
<x-crud-index :list-url="route('pelaksanaankkn.listdata')" />
@stop
