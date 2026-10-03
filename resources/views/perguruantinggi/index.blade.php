@extends('layouts.app')
@section('title','Perguruan Tinggi')
@section('container')
<x-page-header subtitle="Daftar {{ $__env->yieldContent('title') }}" />
<div class="card">
    <div class="card-header">
        <div class="d-flex">
            <form id="form-tambah" method="post" action="{{ url('perguruantinggi/getdata') }}" data-ajax-form>
                @csrf
                @method('PUT')
                <x-button.save formId="form-tambah" icon="ri-loop-left-line">Sync PDDIKTI</x-button.save>
            </form>&nbsp;
            <x-button modal="{{ url('perguruantinggi/tambah') }}" title="Tambah Perguruan Tinggi" icon="ri-play-list-add-line">Tambah Perguruan Tinggi</x-button>
        </div>
    </div>
    <div class="card-body">
        <p id="resultcontent">Loading data...</p>
    </div>
</div>
<script>
    $(function(){
        $("#resultcontent").load("{{ url('perguruantinggi/listdata') }}");
    })
</script>
@stop 