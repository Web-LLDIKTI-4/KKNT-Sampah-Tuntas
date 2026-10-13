@extends('layouts.app')
@section('title','Data PIC/PJ')
@section('container')
<x-crud-index :list-url="url('picpj/listdata')" />
@stop
