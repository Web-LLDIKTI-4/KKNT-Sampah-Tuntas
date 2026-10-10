<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\PendataanPemilahanSampah;
use App\Models\Satuanpendidikan;
use App\Services\PetaSebaranService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetaSebaranTest extends TestCase
{
    use RefreshDatabase;

    private function penempatan(Desa $desa, int $tahun, int $jumlah = 1, array $mhs = []): ?Mahasiswa
    {
        $m = null;
        for ($i = 0; $i < $jumlah; $i++) {
            $m = Mahasiswa::factory()->create($mhs);
            Mahasiswa_lokasi::create(['tahun' => $tahun, 'id_mahasiswa' => $m->id_mahasiswa, 'id_desa' => $desa->id_desa, 'user_in_up' => $m->email]);
        }

        return $m;
    }

    // 1 log: terkelola $terkelola kg dari total 100 kg
    private function logSampah(Mahasiswa $mhs, string $tanggal, float $terkelola): void
    {
        PendataanPemilahanSampah::factory()->create([
            'email' => $mhs->email, 'tanggal' => $tanggal, 'memilah' => true,
            'organik_kg' => $terkelola, 'anorganik_kg' => 0, 'residu_kg' => 100 - $terkelola,
        ]);
    }

    public function test_filter_endpoint_returns_options(): void
    {
        $tahun = (int) date('Y');
        $ptB = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Beta', 'npsn' => '041001']);
        $ptA = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alfa', 'npsn' => '042002']);
        Satuanpendidikan::factory()->create(['nm_lemb' => 'Tanpa Lokasi', 'npsn' => '043003']);
        $ptTanpaKoordinat = Satuanpendidikan::factory()->create(['nm_lemb' => 'Tanpa Koordinat', 'npsn' => '044004']);
        $desa = Desa::factory()->create(['latitude' => -6.9, 'longitude' => 107.6]);
        $this->penempatan($desa, $tahun - 2, 1, ['kodept' => $ptB->npsn]);
        $this->penempatan($desa, $tahun - 2, 1, ['kodept' => $ptA->npsn]);
        $this->penempatan(Desa::factory()->create(), $tahun - 2, 1, ['kodept' => $ptTanpaKoordinat->npsn]);
        Mahasiswa::factory()->create(['kodept' => '043003']);

        $this->getJson(route('login.peta.filter'))->assertOk()->assertExactJson([
            'tahun' => [$tahun, $tahun - 2],
            'tahun_default' => $tahun,
            // Semua PT, tanpa cek kehadiran mahasiswa
            'pt' => [
                ['kodept' => '044004', 'nama' => 'Tanpa Koordinat'],
                ['kodept' => '043003', 'nama' => 'Tanpa Lokasi'],
                ['kodept' => '042002', 'nama' => 'Universitas Alfa'],
                ['kodept' => '041001', 'nama' => 'Universitas Beta'],
            ],
        ]);
    }

    public function test_sebaran_counts_distinct_masks_small_counts_and_hides_id(): void
    {
        $tahun = (int) date('Y');
        $ramai = Desa::factory()->create(['desa' => 'Alfa', 'latitude' => -6.9175, 'longitude' => 107.6191]);
        $sepi = Desa::factory()->create(['desa' => 'Beta', 'latitude' => -6.5, 'longitude' => 107.1]);
        $kosong = Desa::factory()->create(['desa' => 'Delta', 'latitude' => -6.2, 'longitude' => 107.2]);
        $tanpaKoordinat = Desa::factory()->create(['desa' => 'Gamma']);

        $this->penempatan($ramai, $tahun, 12);
        $this->penempatan($ramai, $tahun - 1, 3);
        $this->penempatan($sepi, $tahun, 2);
        $this->penempatan($tanpaKoordinat, $tahun, 5);
        // Baris ganda mahasiswa yang sama tidak dihitung dua kali
        $dup = Mahasiswa_lokasi::where('id_desa', $ramai->id_desa)->where('tahun', $tahun)->first();
        Mahasiswa_lokasi::create(['tahun' => $tahun, 'id_mahasiswa' => $dup->id_mahasiswa, 'id_desa' => $ramai->id_desa, 'user_in_up' => 'x']);

        $response = $this->getJson(route('login.peta'))->assertOk()
            ->assertJsonPath('filter', ['tahun' => $tahun, 'kodept' => null])
            ->assertJsonPath('mode', 'all')
            ->assertJsonPath('summary', ['total_mahasiswa' => 12, 'total_label' => '12+', 'jumlah_desa' => 2, 'jumlah_kecamatan' => 2, 'desa_tersamar' => 1])
            ->assertJsonCount(3, 'desa');

        $this->assertSame(['desa' => 'Alfa', 'kecamatan' => $ramai->kecamatan->kecamatan,
            'latitude' => -6.9175, 'longitude' => 107.6191, 'jumlah_mahasiswa' => 12, 'jumlah_label' => '12', 'tier' => 3, 'persen_pengurangan' => null], $response->json('desa.0'));
        $this->assertSame([null, '<3', 1], [$response->json('desa.1.jumlah_mahasiswa'), $response->json('desa.1.jumlah_label'), $response->json('desa.1.tier')]);
        $this->assertSame([0, '0', 0], [$response->json('desa.2.jumlah_mahasiswa'), $response->json('desa.2.jumlah_label'), $response->json('desa.2.tier')]);
        $this->assertStringNotContainsString($ramai->id_desa, $response->getContent());
        $this->assertStringNotContainsString($ramai->id_kecamatan, $response->getContent());
        $this->assertStringNotContainsString($dup->id_mahasiswa, $response->getContent());
        $this->assertStringNotContainsString('"2"', $response->getContent());
    }

    public function test_summary_label_without_masking_and_tiers(): void
    {
        $tahun = (int) date('Y');
        foreach ([3 => 1, 6 => 2, 21 => 4, 31 => 5] as $jumlah => $tier) {
            $this->penempatan(Desa::factory()->create(['desa' => sprintf('D%02d', $jumlah), 'latitude' => -6.1, 'longitude' => 107.1]), $tahun, $jumlah);
        }

        $response = $this->getJson(route('login.peta'))->assertOk()
            ->assertJsonPath('summary.total_mahasiswa', 61)
            ->assertJsonPath('summary.total_label', '61')
            ->assertJsonPath('summary.desa_tersamar', 0);
        $this->assertSame([1, 2, 4, 5], array_column($response->json('desa'), 'tier'));
    }

    public function test_mode_pt_masks_like_mode_all_and_only_returns_desa_with_students(): void
    {
        $tahun = (int) date('Y');
        $pt = Satuanpendidikan::factory()->create(['npsn' => '041001']);
        $lain = Satuanpendidikan::factory()->create(['npsn' => '042002']);
        foreach ([0, 1, 7] as $jumlah) {
            $desa = Desa::factory()->create(['desa' => sprintf('D%02d', $jumlah), 'latitude' => -6.1, 'longitude' => 107.1]);
            $this->penempatan($desa, $tahun, $jumlah, ['kodept' => $pt->npsn]);
            $this->penempatan($desa, $tahun, 2, ['kodept' => $lain->npsn]);
        }
        // Tahun lain tidak ikut dihitung
        $this->penempatan(Desa::factory()->create(['desa' => 'D99', 'latitude' => -6.2, 'longitude' => 107.2]), $tahun - 1, 9, ['kodept' => $pt->npsn]);

        $response = $this->getJson(route('login.peta', ['kodept' => '041001']))->assertOk()
            ->assertJsonPath('filter', ['tahun' => $tahun, 'kodept' => '041001'])
            ->assertJsonPath('mode', 'pt')
            ->assertJsonPath('summary', ['total_mahasiswa' => 7, 'total_label' => '7+', 'jumlah_desa' => 2, 'jumlah_kecamatan' => 2, 'desa_tersamar' => 1])
            ->assertJsonCount(2, 'desa')
            ->assertJsonCount(2, 'kecamatan');

        $desa = $response->json('desa');
        $this->assertSame(['D01', null, '<3', 1], [$desa[0]['desa'], $desa[0]['jumlah_mahasiswa'], $desa[0]['jumlah_label'], $desa[0]['tier']]);
        $this->assertSame(['D07', 7, '7', 2], [$desa[1]['desa'], $desa[1]['jumlah_mahasiswa'], $desa[1]['jumlah_label'], $desa[1]['tier']]);
        $this->assertArrayNotHasKey('id_kecamatan', $desa[0]);
    }

    public function test_mode_pt_without_students_returns_empty(): void
    {
        $tahun = (int) date('Y');
        Satuanpendidikan::factory()->create(['npsn' => '041001']);
        $this->penempatan(Desa::factory()->create(['latitude' => -6.1, 'longitude' => 107.1]), $tahun, 4);

        $this->getJson(route('login.peta', ['kodept' => '041001']))->assertOk()
            ->assertJsonPath('mode', 'pt')
            ->assertJsonPath('desa', [])
            ->assertJsonPath('kecamatan', [])
            ->assertJsonPath('periode', null)
            ->assertJsonPath('summary', ['total_mahasiswa' => 0, 'total_label' => '0', 'jumlah_desa' => 0, 'jumlah_kecamatan' => 0, 'desa_tersamar' => 0]);
    }

    public function test_persen_pengurangan_per_desa_and_kecamatan_from_last_month_of_tahun(): void
    {
        $tahun = (int) date('Y');
        $pt1 = Satuanpendidikan::factory()->create(['npsn' => '041001']);
        $pt2 = Satuanpendidikan::factory()->create(['npsn' => '042002']);
        $kecA = Kecamatan::factory()->create(['kecamatan' => 'Kec A']);
        $kecB = Kecamatan::factory()->create(['kecamatan' => 'Kec B']);
        $kecC = Kecamatan::factory()->create(['kecamatan' => 'Kec C']);
        $a = Desa::factory()->create(['desa' => 'Alfa', 'id_kecamatan' => $kecA->id_kecamatan, 'latitude' => -6.1, 'longitude' => 107.1]);
        $tanpaKoordinat = Desa::factory()->create(['desa' => 'Gamma', 'id_kecamatan' => $kecA->id_kecamatan]);
        $b = Desa::factory()->create(['desa' => 'Beta', 'id_kecamatan' => $kecB->id_kecamatan, 'latitude' => -6.2, 'longitude' => 107.2]);
        Desa::factory()->create(['desa' => 'Delta', 'id_kecamatan' => $kecC->id_kecamatan, 'latitude' => -6.3, 'longitude' => 107.3]);

        $mA = $this->penempatan($a, $tahun, 3, ['kodept' => $pt1->npsn]);
        $mG = $this->penempatan($tanpaKoordinat, $tahun, 1, ['kodept' => $pt2->npsn]);
        $mB = $this->penempatan($b, $tahun, 3, ['kodept' => $pt2->npsn]);
        $pt3 = Satuanpendidikan::factory()->create(['npsn' => '043003']);
        $mLama = $this->penempatan($a, $tahun, 1, ['kodept' => $pt3->npsn]);
        $this->penempatan($b, $tahun - 1, 1);

        $this->logSampah($mA, $tahun.'-02-10', 90);
        $this->logSampah($mA, $tahun.'-03-10', 23.5);
        $this->logSampah($mG, $tahun.'-03-10', 33.5);
        $this->logSampah($mB, $tahun.'-03-10', 10);
        // PT3 hanya punya input bulan lebih lama dari periode global
        $this->logSampah($mLama, $tahun.'-01-10', 70);
        // Tahun lain tidak menggeser periode
        $this->logSampah($mB, ($tahun + 1).'-01-10', 50);

        $response = $this->getJson(route('login.peta'))->assertOk()
            ->assertJsonPath('periode.bulan', $tahun.'-03')
            ->assertJsonPath('kecamatan', [
                ['kecamatan' => 'Kec A', 'persen_pengurangan' => 28.5],
                ['kecamatan' => 'Kec B', 'persen_pengurangan' => 10],
                ['kecamatan' => 'Kec C', 'persen_pengurangan' => null],
            ]);
        $this->assertSame([23.5, 10, null], array_column($response->json('desa'), 'persen_pengurangan'));
        $this->assertNotEmpty($response->json('periode.label'));
        $this->assertStringNotContainsString($mA->email, $response->getContent());

        // kodept memfilter persen desa & kecamatan
        $this->getJson(route('login.peta', ['kodept' => '041001']))->assertOk()
            ->assertJsonPath('desa.0.persen_pengurangan', 23.5)
            ->assertJsonPath('kecamatan', [['kecamatan' => 'Kec A', 'persen_pengurangan' => 23.5]]);

        // Periode basis PT (sama dengan dashboard PT): bulan terakhir input PT itu sendiri
        $this->getJson(route('login.peta', ['kodept' => '043003']))->assertOk()
            ->assertJsonPath('periode.bulan', $tahun.'-01')
            ->assertJsonPath('desa.0.persen_pengurangan', 70)
            ->assertJsonPath('kecamatan', [['kecamatan' => 'Kec A', 'persen_pengurangan' => 70]]);

        // Tahun tanpa data sampah: periode & semua persen null
        $lalu = $this->getJson(route('login.peta', ['tahun' => $tahun - 1]))->assertOk()
            ->assertJsonPath('periode', null);
        $this->assertSame([null], array_values(array_unique(array_column($lalu->json('desa'), 'persen_pengurangan'))));
        $this->assertSame([null], array_values(array_unique(array_column($lalu->json('kecamatan'), 'persen_pengurangan'))));
    }

    public function test_periode_label_always_indonesian(): void
    {
        $tahun = (int) date('Y');
        app()->setLocale('en');
        $m = $this->penempatan(Desa::factory()->create(['latitude' => -6.1, 'longitude' => 107.1]), $tahun);
        $this->logSampah($m, $tahun.'-05-10', 20);

        $this->getJson(route('login.peta'))->assertOk()
            ->assertJsonPath('periode.label', 'Mei '.$tahun);
    }

    public function test_sebaran_query_count_is_bounded(): void
    {
        $tahun = (int) date('Y');
        foreach (range(1, 5) as $i) {
            $m = $this->penempatan(Desa::factory()->create(['latitude' => -6.1, 'longitude' => 107.1]), $tahun, 3);
            $this->logSampah($m, $tahun.'-03-10', 20);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('login.peta'))->assertOk()->assertJsonCount(5, 'desa');
        $this->assertLessThanOrEqual(6, count(DB::getQueryLog()));
        DB::disableQueryLog();
    }

    public function test_filter_by_tahun_and_kodept_ignores_removed_params(): void
    {
        $tahun = (int) date('Y');
        $pt = Satuanpendidikan::factory()->create(['npsn' => '041001']);
        $a = Desa::factory()->create(['desa' => 'Alfa', 'latitude' => -6.9, 'longitude' => 107.6]);
        $b = Desa::factory()->create(['desa' => 'Beta', 'latitude' => -6.5, 'longitude' => 107.1]);
        $this->penempatan($a, $tahun, 4, ['kodept' => $pt->npsn]);
        $this->penempatan($a, $tahun, 5);
        $this->penempatan($b, $tahun - 1, 7, ['kodept' => $pt->npsn]);

        $this->getJson(route('login.peta', ['tahun' => $tahun - 1, 'kodept' => '041001']))->assertOk()
            ->assertJsonCount(1, 'desa')
            ->assertJsonPath('desa.0.desa', 'Beta')
            ->assertJsonPath('desa.0.jumlah_label', '7')
            ->assertJsonPath('summary.jumlah_desa', 1)
            ->assertJsonPath('summary.jumlah_kecamatan', 1);

        // Param v2 (kecamatan/prodi) diabaikan, bukan 422
        $this->getJson(route('login.peta', ['id_kecamatan' => 'bukan-uuid', 'prodi' => 'Fiktif']))->assertOk()
            ->assertJsonPath('filter', ['tahun' => $tahun, 'kodept' => null])
            ->assertJsonPath('desa.0.jumlah_mahasiswa', 9)
            ->assertJsonPath('desa.1.jumlah_mahasiswa', 0);
    }

    public function test_invalid_filter_returns_422(): void
    {
        $pt = Satuanpendidikan::factory()->create(['npsn' => '041001']);
        $desa = Desa::factory()->create(['latitude' => -6.9, 'longitude' => 107.6]);
        $this->penempatan($desa, (int) date('Y'), 1, ['kodept' => $pt->npsn]);

        $this->getJson(route('login.peta', ['tahun' => 1999]))->assertStatus(422)->assertJsonValidationErrors('tahun');
        $this->getJson(route('login.peta', ['tahun' => 'abc']))->assertStatus(422)->assertJsonValidationErrors('tahun');
        $this->getJson(route('login.peta', ['kodept' => '049999']))->assertStatus(422)->assertJsonValidationErrors('kodept');
        $this->getJson(route('login.peta', ['kodept' => '41001']))->assertStatus(422)->assertJsonValidationErrors('kodept');
        $this->getJson(route('login.peta', ['kodept' => ['041001']]))->assertStatus(422)->assertJsonValidationErrors('kodept');
        $this->getJson(route('login.peta', ['kodept' => str_repeat('0', 21)]))->assertStatus(422)->assertJsonValidationErrors('kodept');
    }

    public function test_peta_limiter_is_separate_from_login_bucket(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson(route('login.peta.filter'))->assertOk();
        }
        $this->getJson(route('login.peta'))->assertStatus(429);
        $this->getJson(route('login.laporan'))->assertOk();
    }

    public function test_observer_flushes_cache_on_desa_and_kecamatan_change(): void
    {
        $desa = Desa::factory()->create(['desa' => 'Alfa', 'latitude' => -6.9, 'longitude' => 107.6]);
        $this->getJson(route('login.peta'))->assertOk()->assertJsonPath('desa.0.latitude', -6.9);
        $this->getJson(route('login.peta.filter'))->assertOk();

        $versi = Cache::get(PetaSebaranService::VERSION_CACHE_KEY, 1);
        $desa->update(['latitude' => -7.1]);
        $this->assertSame($versi + 1, Cache::get(PetaSebaranService::VERSION_CACHE_KEY));
        $this->assertFalse(Cache::has(PetaSebaranService::FILTER_CACHE_KEY));
        $this->getJson(route('login.peta'))->assertOk()->assertJsonPath('desa.0.latitude', -7.1);

        $desa->kecamatan->update(['kecamatan' => 'Kecamatan Baru']);
        $this->getJson(route('login.peta'))->assertOk()->assertJsonPath('desa.0.kecamatan', 'Kecamatan Baru');

        $desa->delete();
        $this->getJson(route('login.peta'))->assertOk()->assertJsonPath('desa', []);
    }

    public function test_listdataserver_includes_float_coordinates(): void
    {
        $this->loginAs('admin');
        Desa::factory()->create(['desa' => 'Alfa', 'latitude' => -6.9175, 'longitude' => 107.6191]);
        Desa::factory()->create(['desa' => 'Beta']);

        $rows = collect($this->getJson('desa/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data'))->keyBy('desa');

        $this->assertSame([-6.9175, 107.6191], [$rows['Alfa']['latitude'], $rows['Alfa']['longitude']]);
        $this->assertSame([null, null], [$rows['Beta']['latitude'], $rows['Beta']['longitude']]);
    }

    public function test_empty_result_without_coordinates(): void
    {
        Desa::factory()->create();

        $this->getJson(route('login.peta'))->assertOk()
            ->assertJsonPath('desa', [])
            ->assertJsonPath('mode', 'all')
            ->assertJsonPath('summary.total_label', '0');
    }

    public function test_authenticated_user_is_redirected_from_guest_endpoint(): void
    {
        $this->loginAs('admin');

        $this->get(route('login.peta'))->assertRedirect();
        $this->get(route('login.peta.filter'))->assertRedirect();
    }

    public function test_admin_can_save_and_clear_coordinates(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create();
        $base = ['id_desa' => $desa->id_desa, 'id_kecamatan' => $desa->id_kecamatan, 'desa' => $desa->desa];

        $this->put('desa/update', $base + ['latitude' => '-6.9175', 'longitude' => '107.6191'])
            ->assertJson(['success' => true]);
        $this->assertEqualsWithDelta(-6.9175, (float) $desa->fresh()->latitude, 0.0000001);
        $this->assertEqualsWithDelta(107.6191, (float) $desa->fresh()->longitude, 0.0000001);

        $this->put('desa/update', $base + ['latitude' => '', 'longitude' => ''])
            ->assertJson(['success' => true]);
        $this->assertNull($desa->fresh()->latitude);
        $this->assertNull($desa->fresh()->longitude);
    }

    public function test_coordinate_validation(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create();
        $base = ['id_kecamatan' => $desa->id_kecamatan, 'desa' => 'Desa Baru'];

        $this->put('desa/insert', $base + ['latitude' => '91', 'longitude' => '107'])
            ->assertJsonPath('errors.latitude.0', 'Latitude harus di antara -90 dan 90.');
        $this->put('desa/insert', $base + ['latitude' => '-6', 'longitude' => '-181'])
            ->assertJsonPath('errors.longitude.0', 'Longitude harus di antara -180 dan 180.');
        $this->put('desa/insert', $base + ['latitude' => 'abc', 'longitude' => '107'])
            ->assertJsonPath('errors.latitude.0', 'Latitude harus berupa angka.');
        $this->put('desa/insert', $base + ['latitude' => '-6.9'])
            ->assertJsonPath('errors.longitude.0', 'Longitude harus diisi jika latitude diisi.');
        $this->put('desa/insert', $base + ['longitude' => '107.6'])
            ->assertJsonPath('errors.latitude.0', 'Latitude harus diisi jika longitude diisi.');
        $this->assertDatabaseMissing('desa', ['desa' => 'Desa Baru']);

        $this->put('desa/insert', $base + ['latitude' => '-90', 'longitude' => '180'])
            ->assertJson(['success' => true]);
    }
}
