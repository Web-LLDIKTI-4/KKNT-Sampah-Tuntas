@extends('layouts.app')
@section('title','FAQ')
@section('container')
<x-crud-index :list-url="url('faq/listdata')" :add-url="url('faq/tambah')" add-title="Tambah FAQ" />
@stop
