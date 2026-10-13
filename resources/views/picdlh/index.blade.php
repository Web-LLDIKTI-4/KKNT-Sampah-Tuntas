@extends('layouts.app')
@section('title','PIC DLH')
@section('container')
<x-crud-index :list-url="url('picdlh/listdata')" :add-url="$canManage ? url('picdlh/tambah') : null" />
@stop
