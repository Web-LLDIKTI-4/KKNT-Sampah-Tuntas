@extends('layouts.app')
@section('title','DPL')
@section('container')
<x-crud-index :list-url="url('ptdpl/listdata')" />
@stop
