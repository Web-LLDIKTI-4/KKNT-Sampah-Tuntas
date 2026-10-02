{{-- Navigasi halaman evaluasi admin; $active: 'hasil' | 'pertanyaan' --}}
<div class="btn-group" role="group" aria-label="Navigasi evaluasi">
    <x-button :href="url('admevaluasikegiatan')" :variant="$active === 'hasil' ? 'info' : 'secondary'" icon="ri-pass-valid-line d-none d-md-block" class="waves-effect waves-light">Data Hasil Evaluasi</x-button>
    <x-button :href="url('admevaluasikegiatan/pertanyaanevaluasi')" :variant="$active === 'pertanyaan' ? 'info' : 'secondary'" icon="ri-questionnaire-line d-none d-md-block" class="waves-effect waves-light">Data Pertanyaan</x-button>
</div>
