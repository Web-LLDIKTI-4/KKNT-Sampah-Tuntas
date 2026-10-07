@extends('layouts.app')
@section('title','Rencana Kerja')
@section('container')
<x-crud-index :list-url="route('rencanakerja.listdata')" :add-url="$canCreate ? route('rencanakerja.tambah') : null" add-title="Tambah Rencana Kerja" />
@stop
