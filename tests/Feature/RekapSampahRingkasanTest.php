<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\PendataanPemilahanSampah;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekapSampahRingkasanTest extends TestCase
{
    use RefreshDatabase;

    public function test_kartu_ringkasan_dihapus_tabel_data_tetap_tampil(): void
    {
        $desa = Desa::factory()->create(['desa' => 'Desa Ringkasan']);
        $mhs = Mahasiswa::factory()->create(['kodept' => Satuanpendidikan::factory()->create()->npsn]);
        Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $desa->id_desa, 'user_in_up' => $mhs->email]);
        foreach ([['Jl. A 1', true], ['Jl. B 2', true], ['Jl. C 3', false]] as [$alamat, $memilah]) {
            PendataanPemilahanSampah::factory()->create([
                'email' => $mhs->email, 'tanggal' => '2026-09-10', 'nama_kepala_keluarga' => 'KK '.$alamat,
                'alamat_rumah' => $alamat, 'memilah' => $memilah, 'organik_kg' => 1, 'anorganik_kg' => 1, 'residu_kg' => 1,
            ]);
        }

        $this->loginAs('admin');
        foreach ([[], ['X-Requested-With' => 'XMLHttpRequest']] as $headers) {
            $html = $this->get('rekapsampah?bulan=2026-09', $headers)->assertOk()
                ->assertSee('Subtotal per Lokasi Program')
                ->assertSee('Detail per Log Pendataan')
                ->getContent();

            // Label kartu (div sendiri); header tabel memuat rumus sehingga tidak ikut cocok
            foreach (['Persentase Pengelolaan Sampah', 'Penurunan Timbulan', 'Persentase Ketaatan Pemilahan', 'Ketaatan Pemilahan', 'Total Dihasilkan', 'Total Terkelola', 'Residu'] as $label) {
                $this->assertStringNotContainsString('<div class="small text-muted">'.$label.'</div>', $html);
            }
            $this->assertDoesNotMatchRegularExpression('/\d+ dari \d+ rumah/', $html);
            $this->assertStringNotContainsString('flex-wrap gap-5', $html);
        }
    }

    public function test_tamu_dan_role_lain_ditolak(): void
    {
        $this->get('rekapsampah')->assertRedirect(route('login'));

        $this->loginAs('mahasiswa');
        $this->get('rekapsampah')->assertRedirect(route('home'));
    }
}
