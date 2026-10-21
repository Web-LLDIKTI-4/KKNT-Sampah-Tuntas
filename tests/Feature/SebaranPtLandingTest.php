<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

use App\Models\Dpl;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
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
            ->assertSeeInOrder(['No.</th>', 'Kode PT</th>', 'Nama PTS</th>', 'Lokasi Program</th>'], false)
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

        $baru = LokasiProgram::factory()->create(['nama_lokasi' => 'Kab Garut']);
        Mahasiswa::factory()->create(['kodept' => $alfa->npsn, 'location_program' => $baru->id]);
        $this->get('/')->assertViewHas('sebaranPtList', fn ($l) => $l->count() === 4);

        PetaSebaranService::flushCache();
        $this->get('/')->assertViewHas('sebaranPtList', fn ($l) => $l->count() === 5);
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
}
