{{-- Aksi ketua kelompok per baris DataTable ptmahasiswa (dirender PtmahasiswaController::listdataserver) --}}
@if ($row->ketua_id)
    <x-button.icon action="delete" class="btn-delete" title="Cabut Ketua" aria-label="Cabut Ketua"
        data-url="{{ route('ptmahasiswa.cabutketua') }}" data-id-field="id_pjdesa" data-id-value="{{ $row->ketua_id }}"
        data-confirm="Cabut status ketua kelompok dari {{ $row->nama }}?" />
@elseif ($row->nama_desa && $row->punya_akun)
    <x-button variant="outline-primary" icon="ri-user-star-line" class="btn-confirm-put text-nowrap"
        data-url="{{ route('ptmahasiswa.setketua') }}" data-id-field="id_mahasiswa" data-id-value="{{ $row->id_mahasiswa }}"
        data-confirm="Jadikan {{ $row->nama }} ketua kelompok di desa lokasi KKN-nya?"
        data-error="Terjadi kesalahan saat menetapkan ketua kelompok">Jadikan Ketua</x-button>
@else
    -
@endif
