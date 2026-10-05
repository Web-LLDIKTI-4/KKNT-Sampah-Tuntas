{{-- Data sampah bulan yang sama dengan capaian; $sampah: Kpisampah|null, $desa: Desa|null --}}
<hr class="my-6">
<div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-4">
    <h5 class="mb-0">Data Sampah Bulanan</h5>
    <small class="text-muted">Kel. {{ $desa?->desa ?? '-' }}, Kec. {{ $desa?->kecamatan?->kecamatan ?? '-' }} · bulan mengikuti Tanggal Capaian</small>
</div>
@unless ($desa)
    <div class="alert alert-warning">Anda belum memilih kelurahan pada profil atau belum terdaftar sebagai ketua kelompok.</div>
@endunless
@include('kpisampah._fields', ['data' => $sampah ?? null])
