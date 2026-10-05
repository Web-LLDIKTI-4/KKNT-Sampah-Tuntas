@php
    $formId = $data ? 'form-ubah' : 'form-tambah';
@endphp
<form id="{{ $formId }}" method="post" action="{{ url($data ? 'kpisampah/update' : 'kpisampah/insert') }}" data-ajax-form data-sampah-form data-reload="#dataTableSampah">
    @csrf
    @method('PUT')
    @if ($data)
        <input type="hidden" name="id_sampah" value="{{ $data->id_sampah }}">
    @endif

    @unless ($desa)
        <div class="alert alert-warning">Anda belum memilih kelurahan pada profil atau belum terdaftar sebagai ketua kelompok.</div>
    @endunless

    <div class="row">
        <div class="col-md-4 form-floating form-floating-outline mb-6">
            <input type="month" name="bulan" class="form-control form-control-sm" required min="2020-01" max="{{ now()->format('Y-m') }}" value="{{ $data?->bulan->format('Y-m') ?? now()->format('Y-m') }}">
            <label>Bulan</label>
        </div>
        <div class="col-md-4 form-floating form-floating-outline mb-6">
            <input type="text" class="form-control form-control-sm" value="{{ $desa?->kecamatan?->kecamatan ?? '-' }}" readonly tabindex="-1">
            <label>Kecamatan</label>
        </div>
        <div class="col-md-4 form-floating form-floating-outline mb-6">
            <input type="text" class="form-control form-control-sm" value="{{ $desa?->desa ?? '-' }}" readonly tabindex="-1">
            <label>Kelurahan / Desa</label>
        </div>
    </div>

    @include('kpisampah._fields', ['data' => $data])
    <hr>
    <x-button.save :formId="$formId">Simpan</x-button.save>
</form>
