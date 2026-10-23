<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Satuanpendidikan;
use App\Services\PetaSebaranService;
use App\Services\SebaranPtSummary;

class SebaranPtLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function seedPasangan(): array
    {
        $bandung = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Bandung']);
        $cimahi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Cimahi']);
        $alfa = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alfa']);
        $beta = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Beta']);

        // Alfa di 2 lokasi (mahasiswa + dpl, duplikat pasangan harus jadi satu baris)
        Mahasiswa::factory()->count(2)->create(['kodept' => $alfa->npsn, 'location_program' => $cimahi->id]);
        Dpl::factory()->create(['kodept' => $alfa->npsn, 'location_program' => $bandung->id]);
        Mahasiswa::factory()->create(['kodept' => $alfa->npsn, 'location_program' => $bandung->id]);
        // Beta hanya punya DPL
        Dpl::factory()->create(['kodept' => $beta->npsn, 'location_program' => $bandung->id]);
        // PT tanpa ref_satuanpendidikan → nama fallback ke kodept
        Mahasiswa::factory()->create(['kodept' => '0000000001', 'location_program' => $cimahi->id]);

        return [$alfa, $beta];
    }

    public function test_summary_returns_one_row_per_pt_lokasi_pair_sorted(): void
    {
        [$alfa, $beta] = $this->seedPasangan();

        $rows = app(SebaranPtSummary::class)->all()
            ->map(fn ($r) => [$r->kodept, $r->nama_pt, $r->nama_lokasi])->all();

        $this->assertSame([
            ['0000000001', '0000000001', 'Kota Cimahi'],
            [$alfa->npsn, 'Universitas Alfa', 'Kota Bandung'],
            [$alfa->npsn, 'Universitas Alfa', 'Kota Cimahi'],
            [$beta->npsn, 'Universitas Beta', 'Kota Bandung'],
        ], $rows);

        $this->assertSame(['Kota Bandung', 'Kota Cimahi'], app(SebaranPtSummary::class)->lokasiPerPt()[$alfa->npsn]);
    }

    public function test_landing_has_sebaran_section_after_peserta_with_columns_and_rows(): void
    {
        [$alfa] = $this->seedPasangan();

        $response = $this->get('/')->assertOk()
            ->assertViewHas('sebaranPtList', fn ($list) => $list->count() === 4)
            ->assertSeeInOrder(['id="peserta"', 'id="sebaran"', 'id="peta"'], false)
            ->assertSeeInOrder(['No.</th>', 'Kode PT<i class="ri-arrow-up-down-line"', 'Nama PTS<i class="ri-arrow-up-down-line"', 'Lokasi Program<i class="ri-arrow-up-down-line"'], false)
            ->assertSeeInOrder(['<td>Universitas Alfa</td>', '<td>Kota Bandung</td>', '<td>Universitas Alfa</td>', '<td>Kota Cimahi</td>'], false);

        $html = $response->getContent();
        $sebaran = substr($html, strpos($html, 'id="sebaran"'), strpos($html, 'id="peta"') - strpos($html, 'id="sebaran"'));
        $this->assertSame(4, substr_count($sebaran, '<tr data-search='));
        $this->assertSame(2, substr_count($sebaran, '<td class="text-center">'.$alfa->npsn.'</td>'));
    }

    public function test_sebaran_is_in_navbar(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('href="#sebaran"', false)
            ->assertSeeInOrder(['href="#peserta"', 'href="#sebaran"'], false);
    }

    public function test_landing_sebaran_empty_state(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('id="sebaran"', false)
            ->assertSee('Data sebaran perguruan tinggi belum tersedia.');
    }

    public function test_sebaran_values_are_escaped(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => '<script>alert(1)</script>']);
        $pt = Satuanpendidikan::factory()->create(['nm_lemb' => '<img src=x onerror=alert(2)>']);
        Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $lokasi->id]);

        $this->get('/')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(2)>', false)
            ->assertSee('&lt;img src=x onerror=alert(2)&gt;', false);
    }

    public function test_sebaran_cache_follows_peta_version(): void
    {
        [$alfa] = $this->seedPasangan();
        $this->get('/')->assertViewHas('sebaranPtList', fn ($l) => $l->count() === 4);

        // Eloquent write → observer bumps version, landing fresh without manual flush
        $baru = LokasiProgram::factory()->create(['nama_lokasi' => 'Kab Garut']);
        Mahasiswa::factory()->create(['kodept' => $alfa->npsn, 'location_program' => $baru->id]);
        $this->get('/')->assertViewHas('sebaranPtList', fn ($l) => $l->count() === 5);

        // Write bypassing events stays cached until version is bumped
        $garut2 = LokasiProgram::factory()->create(['nama_lokasi' => 'Kab Garut 2']);
        Mahasiswa::withoutEvents(fn () => Mahasiswa::factory()->create(['kodept' => $alfa->npsn, 'location_program' => $garut2->id]));
        $this->get('/')->assertViewHas('sebaranPtList', fn ($l) => $l->count() === 5);

        PetaSebaranService::flushCache();
        $this->get('/')->assertViewHas('sebaranPtList', fn ($l) => $l->count() === 6);
    }

    // AC6: angka Data Peserta jadi link polos
    public function test_peserta_numbers_use_only_peserta_detail_class(): void
    {
        $this->seedPasangan();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('btn btn-link p-0', $html);
        $this->assertMatchesRegularExpression('/class="peserta-detail" data-kodept=/', $html);
        preg_match_all('/<[a-z]+[^>]*class="([^"]*\bpeserta-detail\b[^"]*)"[^>]*data-jenis=/', $html, $m);
        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $class) {
            $this->assertSame('peserta-detail', $class);
        }
    }

    private function placed(string $kodept, LokasiProgram $lokasi, Desa $desa, ?int $tahun = null): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create(['kodept' => $kodept, 'location_program' => $lokasi->id]);
        Mahasiswa_lokasi::create(['tahun' => $tahun ?? (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $desa->id_desa]);

        return $mhs;
    }

    private function desa(string $kecamatan, string $desa): Desa
    {
        $kec = Kecamatan::firstWhere('kecamatan', $kecamatan) ?? Kecamatan::factory()->create(['kecamatan' => $kecamatan]);

        return Desa::factory()->create(['id_kecamatan' => $kec->id_kecamatan, 'desa' => $desa]);
    }

    private function rows(): array
    {
        return app(SebaranPtSummary::class)->all()
            ->map(fn ($r) => [$r->kodept, $r->nama_lokasi, $r->nama_kecamatan, $r->nama_desa])->all();
    }

    public function test_pt_in_two_desa_same_lokasi_gives_two_rows_with_kecamatan_desa(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Bandung']);
        $pt = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alfa']);
        $this->placed($pt->npsn, $lokasi, $this->desa('Kec Coblong', 'Desa Dago'));
        $this->placed($pt->npsn, $lokasi, $this->desa('Kec Sukajadi', 'Desa Pasteur'));
        // Same desa twice must not duplicate
        $this->placed($pt->npsn, $lokasi, Desa::firstWhere('desa', 'Desa Dago'));

        $this->assertSame([
            [$pt->npsn, 'Kota Bandung', 'Kec Coblong', 'Desa Dago'],
            [$pt->npsn, 'Kota Bandung', 'Kec Sukajadi', 'Desa Pasteur'],
        ], $this->rows());
    }

    public function test_uses_latest_mahasiswa_lokasi_tahun(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Bandung']);
        $pt = Satuanpendidikan::factory()->create();
        $mhs = $this->placed($pt->npsn, $lokasi, $this->desa('Kec Lama', 'Desa Lama'), 2024);
        Mahasiswa_lokasi::create(['tahun' => 2025, 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $this->desa('Kec Baru', 'Desa Baru')->id_desa]);

        $this->assertSame([[$pt->npsn, 'Kota Bandung', 'Kec Baru', 'Desa Baru']], $this->rows());
    }

    public function test_dpl_only_pair_has_null_kecamatan_desa_and_renders_dash(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Bandung']);
        $pt = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Beta']);
        Dpl::factory()->count(2)->create(['kodept' => $pt->npsn, 'location_program' => $lokasi->id]);

        $this->assertSame([[$pt->npsn, 'Kota Bandung', null, null]], $this->rows());

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['<td>Universitas Beta</td>', '<td>Kota Bandung</td>', '<td>-</td>', '<td>-</td>'], false);
    }

    public function test_pair_with_desa_and_unplaced_mahasiswa_has_no_null_row(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Bandung']);
        $pt = Satuanpendidikan::factory()->create();
        $this->placed($pt->npsn, $lokasi, $this->desa('Kec Coblong', 'Desa Dago'));
        Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $lokasi->id]);
        Dpl::factory()->create(['kodept' => $pt->npsn, 'location_program' => $lokasi->id]);

        $this->assertSame([[$pt->npsn, 'Kota Bandung', 'Kec Coblong', 'Desa Dago']], $this->rows());
    }

    public function test_lokasi_per_pt_has_no_duplicate_lokasi(): void
    {
        $bandung = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Bandung']);
        $cimahi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Cimahi']);
        $pt = Satuanpendidikan::factory()->create();
        $this->placed($pt->npsn, $bandung, $this->desa('Kec Coblong', 'Desa Dago'));
        $this->placed($pt->npsn, $bandung, $this->desa('Kec Sukajadi', 'Desa Pasteur'));
        $this->placed($pt->npsn, $cimahi, $this->desa('Kec Cimahi', 'Desa Cibabat'));

        $this->assertCount(3, app(SebaranPtSummary::class)->all());
        $this->assertSame(['Kota Bandung', 'Kota Cimahi'], app(SebaranPtSummary::class)->lokasiPerPt()[$pt->npsn]);
    }

    public function test_landing_shows_kecamatan_desa_columns_search_and_escapes(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Bandung']);
        $pt = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alfa']);
        $this->placed($pt->npsn, $lokasi, $this->desa('Kec Coblong', 'Desa Dago'));
        $this->placed($pt->npsn, $lokasi, $this->desa('Kec <b>X</b>', '<script>alert(3)</script>'));

        $response = $this->get('/')->assertOk()
            ->assertSeeInOrder(['Lokasi Program<i class="ri-arrow-up-down-line"', 'Kecamatan<i class="ri-arrow-up-down-line"', 'Kelurahan/Desa<i class="ri-arrow-up-down-line"'], false)
            ->assertSeeInOrder(['<td>Kota Bandung</td>', '<td>Kec Coblong</td>', '<td>Desa Dago</td>'], false)
            ->assertSee('data-search="'.mb_strtolower($pt->npsn.' Universitas Alfa Kota Bandung Kec Coblong Desa Dago').'"', false)
            ->assertDontSee('<script>alert(3)</script>', false)
            ->assertDontSee('Kec <b>X</b>', false)
            ->assertSee('<td>&lt;script&gt;alert(3)&lt;/script&gt;</td>', false)
            ->assertSee('<td>Kec &lt;b&gt;X&lt;/b&gt;</td>', false);

        $this->assertStringContainsString('&lt;script&gt;alert(3)&lt;/script&gt;"', $response->getContent());
    }

    public function test_status_lokasi_counts_only_sebaran_mahasiswa(): void
    {
        $lokasi = LokasiProgram::factory()->create();
        $pt = Satuanpendidikan::factory()->create();
        $desa = $this->desa('Kec A', 'Desa A');

        // Sudah: multi-tahun tetap dihitung sekali
        $mhs = $this->placed($pt->npsn, $lokasi, $desa, 2024);
        Mahasiswa_lokasi::create(['tahun' => 2025, 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $desa->id_desa]);
        // Belum: tanpa baris lokasi / id_desa NULL
        [$belum] = Mahasiswa::factory()->count(2)->create(['kodept' => $pt->npsn, 'location_program' => $lokasi->id]);
        Mahasiswa_lokasi::create(['tahun' => 2025, 'id_mahasiswa' => $belum->id_mahasiswa, 'id_desa' => null]);
        // Di luar populasi sebaran, walau punya desa
        $luar = Mahasiswa::factory()->create(['kodept' => null, 'location_program' => $lokasi->id]);
        Mahasiswa_lokasi::create(['tahun' => 2025, 'id_mahasiswa' => $luar->id_mahasiswa, 'id_desa' => $desa->id_desa]);
        Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => null]);

        $this->assertSame(['sudah' => 1, 'belum' => 2, 'total' => 3], app(SebaranPtSummary::class)->statusLokasi());
        $this->get('/')->assertOk()->assertViewHas('statusLokasi', ['sudah' => 1, 'belum' => 2, 'total' => 3]);
    }

    public function test_status_lokasi_updates_immediately_after_set_lokasi(): void
    {
        $lokasi = LokasiProgram::factory()->create();
        $pt = Satuanpendidikan::factory()->create();
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $lokasi->id]);

        $this->get('/')->assertViewHas('statusLokasi', ['sudah' => 0, 'belum' => 1, 'total' => 1]);

        Mahasiswa_lokasi::create(['tahun' => 2025, 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $this->desa('Kec B', 'Desa B')->id_desa]);

        $this->get('/')->assertViewHas('statusLokasi', ['sudah' => 1, 'belum' => 0, 'total' => 1]);
    }
}
