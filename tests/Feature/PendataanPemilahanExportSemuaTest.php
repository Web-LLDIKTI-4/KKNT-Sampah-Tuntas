<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Dplmentoring;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\PendataanPemilahanSampah;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ReadsXlsx;
use Tests\TestCase;

class PendataanPemilahanExportSemuaTest extends TestCase
{
    use ReadsXlsx;
    use RefreshDatabase;

    private const URL = 'pendataanpemilahan/export-semua';

    private array $d = [];

    // ptA: mhsA1 (bimbingan dplA), mhsA2; ptB: mhsB1
    protected function setUp(): void
    {
        parent::setUp();
        $this->d['ptA'] = Satuanpendidikan::factory()->create();
        $this->d['ptB'] = Satuanpendidikan::factory()->create();
        foreach (['mhsA1' => 'ptA', 'mhsA2' => 'ptA', 'mhsB1' => 'ptB'] as $key => $pt) {
            $mhs = Mahasiswa::factory()->create(['kodept' => $this->d[$pt]->npsn, 'email' => $key.'@uji.test']);
            Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => Desa::factory()->create()->id_desa, 'user_in_up' => $mhs->email]);
            PendataanPemilahanSampah::factory()->create(['email' => $mhs->email, 'tanggal' => '2026-09-05', 'memilah' => true]);
            PendataanPemilahanSampah::factory()->create(['email' => $mhs->email, 'tanggal' => '2026-10-05']);
            $this->d[$key] = $mhs;
        }
        Dplmentoring::create(['email_mahasiswa' => 'mhsA1@uji.test', 'email_dpl' => 'dplA@uji.test']);
    }

    private function user(string $role, string $email): User
    {
        return User::factory()->role($role)->create(['email' => $email]);
    }

    private function emailDiExport(User $user, string $query = ''): array
    {
        $ws = $this->xlsxFromResponse($this->actingAs($user)->get(self::URL.$query), '/filename=pendataan_pemilahan_semua_.+\.xlsx/');
        $kolom = array_search('Email Address', $this->headerRow($ws), true);

        return collect($this->dataColumn($ws, $kolom))->unique()->sort()->values()->all();
    }

    public function test_pt_hanya_mahasiswa_pt_nya_dan_tidak_bisa_override(): void
    {
        $pt = $this->user('pt', $this->d['ptA']->npsn);

        $this->assertSame(['mhsA1@uji.test', 'mhsA2@uji.test'], $this->emailDiExport($pt));
        $this->assertSame(['mhsA1@uji.test', 'mhsA2@uji.test'], $this->emailDiExport($pt, '?kodept='.$this->d['ptB']->npsn.'&email=mhsB1@uji.test'));
    }

    public function test_dpl_hanya_mahasiswa_bimbingan(): void
    {
        $this->assertSame(['mhsA1@uji.test'], $this->emailDiExport($this->user('dpl', 'dplA@uji.test')));
        $this->assertSame([], $this->emailDiExport($this->user('dpl', 'dplTanpa@uji.test')));
    }

    public function test_header_dan_isi_baris(): void
    {
        $ws = $this->xlsxFromResponse($this->actingAs($this->user('dpl', 'dplA@uji.test'))->get(self::URL.'?bulan=2026-09'), '/filename=pendataan_pemilahan_semua_2026-09_.+\.xlsx/');

        $this->assertSame(['No.', 'Tanggal', 'Email Address', 'Nama Mahasiswa', 'NIM', 'Kabupaten/Kota', 'Nama Kecamatan', 'Nama Kelurahan/Desa',
            'Nama Kepala Keluarga', 'Anggota Keluarga', 'Alamat Rumah', 'RT', 'RW', 'Memilah', 'Organik (kg)', 'Anorganik (kg)', 'Residu (kg)'], $this->headerRow($ws));
        $rows = $this->dataRows($ws);
        $this->assertCount(1, $rows);
        $this->assertSame('05-09-2026', (string) $rows[0][1]);
        $this->assertSame($this->d['mhsA1']->nama, (string) $rows[0][3]);
        $this->assertSame('Ya', (string) $rows[0][13]);
    }

    public function test_bulan_opsional_dan_divalidasi(): void
    {
        $pt = $this->user('pt', $this->d['ptA']->npsn);
        $this->actingAs($pt);

        $this->assertCount(4, $this->dataRows($this->xlsxFromResponse($this->get(self::URL.'?bulan='), '/filename=pendataan_pemilahan_semua_semua_.+\.xlsx/')));
        $this->assertCount(2, $this->dataRows($this->xlsxFromResponse($this->get(self::URL.'?bulan=2026-10'), '/filename=pendataan_pemilahan_semua_2026-10_.+\.xlsx/')));
        $this->get(self::URL.'?bulan=2026-13')->assertSessionHasErrors('bulan');
    }

    public function test_role_lain_ditolak(): void
    {
        foreach (['mahasiswa' => 'mhsA1@uji.test', 'admin' => 'admin@uji.test', 'kepala' => 'kepala@uji.test', 'pemda' => 'pemda@uji.test'] as $role => $email) {
            $response = $this->actingAs($this->user($role, $email))->get(self::URL);
            $this->assertNotSame(200, $response->getStatusCode(), $role);
        }
        auth()->logout();
        $this->get(self::URL)->assertRedirect();
    }

    public function test_throttle_3_per_menit(): void
    {
        $this->actingAs($this->user('dpl', 'dplA@uji.test'));
        for ($i = 0; $i < 3; $i++) {
            $this->get(self::URL)->assertOk();
        }
        $this->get(self::URL)->assertStatus(429);
    }

    public function test_tombol_hanya_untuk_dpl_dan_pt(): void
    {
        $route = route('pendataanpemilahan.export.semua');
        $this->actingAs($this->user('dpl', 'dplA@uji.test'))->get('pendataanpemilahan')->assertOk()->assertSee($route, false);
        $this->actingAs($this->user('pt', $this->d['ptA']->npsn))->get('pendataanpemilahan')->assertOk()->assertSee($route, false);
        $this->actingAs($this->user('admin', 'admin@uji.test'))->get('pendataanpemilahan')->assertOk()->assertDontSee($route, false);
    }
}
