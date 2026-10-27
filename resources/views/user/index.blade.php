@extends('layouts.app')
@section('title','Kelola Pengguna')
@section('container')
<x-page-header /> 
<div class="card">
    <div class="card-header d-flex flex-column flex-md-row gap-3">
        <x-button modal="{{ url('user/getdatamember') }}" title="Tambah Pengguna Mahasiswa" icon="ri-user-add-fill">Tambah Pengguna Mahasiswa
        </x-button>
        <x-button modal="{{ url('user/adduser') }}" variant="success" title="Tambah Pengguna DPL" icon="ri-user-add-fill">Tambah Pengguna DPL
        </x-button>
        <x-button modal="{{ url('user/adduserpt') }}" variant="warning" title="Tambah Pengguna Perguruan Tinggi" icon="ri-user-add-fill">Tambah Pengguna Perguruan Tinggi
        </x-button>
        <x-button modal="{{ url('user/adduserkepala') }}" variant="info" title="Tambah Pengguna Kepala/Pemda" icon="ri-user-add-fill">Tambah Pengguna Kepala/Pemda
        </x-button>
        <x-button.export id="btn-export-user" url="{{ route('user.export') }}" table="#dataTable-user" data-export-state />
    </div>
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row gap-3 mb-4">
            <div class="col-12 col-md-3">
                <label for="filter-role" class="form-label">Peran</label>
                <select id="filter-role" class="form-select user-filter">
                    <option value="">Semua Peran</option>
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="dpl">DPL</option>
                    <option value="pt">Perguruan Tinggi</option>
                    <option value="kepala">Kepala</option>
                    <option value="pemda">Pemda</option>
                </select>
            </div>
            <div class="col-12 col-md-5">
                <label for="filter-kodept" class="form-label">Perguruan Tinggi</label>
                <select id="filter-kodept" class="form-select user-filter">
                    <option value="">Semua Perguruan Tinggi</option>
                    @foreach($perguruanTinggi as $npsn => $nama)
                        <option value="{{ $npsn }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex align-items-end">
                <x-button id="reset-filter" variant="outline-secondary" size="" icon="ri-refresh-line">Reset Filter</x-button>
            </div>
        </div>
        <p id="resultcontent">loading user...</p>
    </div>
</div>
<script>
$(function(){
    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-xl');
    })
    function loadList(params) {
        loadPartial('#resultcontent', "{{ url('user/listdata') }}", { data: params, loadingText: 'loading user...' });
    }

    loadList({});

    $('.user-filter').on('change', function () {
        var params = {};
        var role = $('#filter-role').val();
        var kodept = $('#filter-kodept').val();
        if (role) params.role = role;
        if (kodept) params.kodept = kodept;
        loadList(params);
    });

    // Clear both filters, then reuse the change handler for a single reload
    $('#reset-filter').on('click', function () {
        $('.user-filter').val('').trigger('change.select2');
        $('#filter-role').trigger('change');
    });

})    
</script>
@stop 