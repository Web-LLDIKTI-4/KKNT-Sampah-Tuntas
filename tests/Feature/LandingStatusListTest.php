<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\PelaksanaanKkn;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Services\DataPesertaSummary;
use App\Services\SebaranPtSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LandingStatusListTest extends TestCase
{
    use RefreshDatabase;

    private const FIELDS = [
        'mobilisasi' => ['nim', 'nama', 'nama_pt', 'kecamatan', 'desa'],
        'pelaksanaan' => ['kode_pt', 'nama_pt'],
        'lokasi' => ['nim', 'nama', 'nama_pt', 'lokasi', 'kecamatan', 'desa'],
    ];

    private Satuanpendidikan $ptA;

    private Satuanpendidikan $ptB;

    private Desa $desaLama;

    private Desa $desaBaru;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function list(array $params)
    {
        return $this->getJson(route('login.status-list', $params));
    }

    // Edge cases: duplicate email, pj_desa without mahasiswa, non-'sudah' status, multi-year lokasi, id_desa NULL, PT dpl only
    private function seedStatusData(): void
    {
        $this->ptA = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alpha']);
        $this->ptB = Satuanpendidikan::factory()->create(['nm_lemb' => 'Institut 100% Beta']);
        $ptC = Satuanpendidikan::factory()->create(['nm_lemb' => 'Politeknik Gamma']);
        $luar = Satuanpendidikan::factory()->create(['nm_lemb' => 'PT Luar']);
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Uji']);
        $kec = Kecamatan::factory()->create(['kecamatan' => 'Kec Uji']);
        $this->desaLama = Desa::factory()->create(['id_kecamatan' => $kec->id_kecamatan, 'desa' => 'Desa Lama']);
        $this->desaBaru = Desa::factory()->create(['id_kecamatan' => $kec->id_kecamatan, 'desa' => 'Desa Baru']);

        $andi = Mahasiswa::factory()->create(['nama' => 'Andi', 'nim' => '1110001', 'kodept' => $this->ptA->npsn, 'location_program' => $lokasi->id, 'email' => 'andi@pps.test']);
        Mahasiswa::factory()->create(['nama' => 'Budi_Dua', 'nim' => '1110002', 'kodept' => $this->ptA->npsn, 'location_program' => $lokasi->id, 'email' => 'dup@pps.test']);
        Mahasiswa::factory()->create(['nama' => 'BudiXDua', 'nim' => '1110003', 'kodept' => $this->ptB->npsn, 'location_program' => $lokasi->id, 'email' => 'dup@pps.test']);
        Mahasiswa::factory()->create(['nama' => null, 'nim' => '1110004', 'kodept' => $this->ptB->npsn, 'location_program' => $lokasi->id]);
        $citra = Mahasiswa::factory()->create(['nama' => 'Citra', 'nim' => '1110005', 'kodept' => $this->ptB->npsn, 'location_program' => $lokasi->id]);
        Mahasiswa::factory()->create(['nama' => 'Dedi', 'nim' => '1110006', 'kodept' => $this->ptA->npsn]);
        Dpl::factory()->create(['kodept' => $ptC->npsn]);

        foreach ([[2025, $this->desaLama], [2026, $this->desaBaru]] as [$tahun, $desa]) {
            Mahasiswa_lokasi::create(['tahun' => $tahun, 'id_mahasiswa' => $andi->id_mahasiswa, 'id_desa' => $desa->id_desa, 'user_in_up' => $andi->email]);
        }
        Mahasiswa_lokasi::create(['tahun' => 2026, 'id_mahasiswa' => $citra->id_mahasiswa, 'id_desa' => null, 'user_in_up' => $citra->email]);

        Pjdesa::create(['email' => 'andi@pps.test', 'id_desa' => $this->desaLama->id_desa, 'status_mobilisasi' => 'sudah']);
        Pjdesa::create(['email' => 'dup@pps.test', 'id_desa' => $this->desaBaru->id_desa, 'status_mobilisasi' => 'belum']);
        Pjdesa::create(['email' => 'yatim@pps.test', 'id_desa' => $this->desaBaru->id_desa]);
        Pjdesa::create(['email' => 'x@pps.test', 'id_desa' => $this->desaBaru->id_desa, 'status_mobilisasi' => 'x']);

        PelaksanaanKkn::factory()->forPt($this->ptA->npsn)->create();
        PelaksanaanKkn::factory()->forPt($luar->npsn)->create();
    }

    private function expectedCounts(): array
    {
        $peserta = app(DataPesertaSummary::class);
        $status = $peserta->status($peserta->all()->pluck('kodept'));

        return $status + ['lokasi' => app(SebaranPtSummary::class)->statusLokasi()];
    }

    public function test_meta_totals_match_landing_badge_counts_for_every_jenis(): void
    {
        $this->seedStatusData();
        $expected = $this->expectedCounts();
        $this->assertSame(['sudah' => 1, 'belum' => 3, 'total' => 4], $expected['mobilisasi']);
        $this->assertSame(['sudah' => 1, 'belum' => 2, 'total' => 3], $expected['pelaksanaan']);
        $this->assertSame(['sudah' => 1, 'belum' => 4, 'total' => 5], $expected['lokasi']);

        foreach (array_keys(self::FIELDS) as $jenis) {
            $sum = 0;
            foreach (['sudah', 'belum'] as $status) {
                $res = $this->list(['jenis' => $jenis, 'status' => $status, 'per_page' => 25])->assertOk()
                    ->assertJsonPath('jenis', $jenis)->assertJsonPath('status', $status);
                $this->assertSame($expected[$jenis][$status], $res->json('meta.total'), "$jenis/$status");
                $this->assertCount($expected[$jenis][$status], $res->json('data'), "$jenis/$status rows");
                $sum += $res->json('meta.total');
            }
            $this->assertSame($expected[$jenis]['total'], $sum, $jenis);
        }
    }

    public function test_data_rows_expose_only_whitelisted_fields(): void
    {
        $this->seedStatusData();

        foreach (self::FIELDS as $jenis => $fields) {
            foreach (['sudah', 'belum'] as $status) {
                $res = $this->list(['jenis' => $jenis, 'status' => $status, 'per_page' => 25])->assertOk();
                $this->assertSame(['jenis', 'status', 'data', 'meta'], array_keys($res->json()));
                $this->assertSame(['page', 'per_page', 'total', 'last_page', 'from', 'to', 'sort', 'dir'], array_keys($res->json('meta')));
                foreach ($res->json('data') as $row) {
                    $this->assertSame($fields, array_keys($row), "$jenis/$status");
                }
                $raw = $res->getContent();
                $this->assertDoesNotMatchRegularExpression('/"(email|phone|no_hp|id|id_[a-z_]+|kodept|location_program)"\s*:/', $raw, "$jenis/$status");
                $this->assertStringNotContainsString('@pps.test', $raw);
            }
        }
    }

    public function test_mobilisasi_rows_dedupe_email_and_keep_orphan_pjdesa_last(): void
    {
        $this->seedStatusData();

        $this->list(['jenis' => 'mobilisasi', 'status' => 'sudah'])->assertOk()
            ->assertExactJson([
                'jenis' => 'mobilisasi', 'status' => 'sudah',
                'data' => [['nim' => '1110001', 'nama' => 'Andi', 'nama_pt' => 'Universitas Alpha', 'kecamatan' => 'Kec Uji', 'desa' => 'Desa Lama']],
                'meta' => ['page' => 1, 'per_page' => 10, 'total' => 1, 'last_page' => 1, 'from' => 1, 'to' => 1, 'sort' => null, 'dir' => 'asc'],
            ]);

        $data = $this->list(['jenis' => 'mobilisasi', 'status' => 'belum'])->assertOk()->json('data');
        $this->assertCount(3, $data);
        $this->assertContains($data[0]['nim'], ['1110002', '1110003']);
        $this->assertSame('Desa Baru', $data[0]['desa']);
        foreach ([1, 2] as $i) {
            $this->assertNull($data[$i]['nama']);
            $this->assertNull($data[$i]['nim']);
            $this->assertSame('Desa Baru', $data[$i]['desa']);
        }
    }

    public function test_pelaksanaan_lists_peserta_pt_only(): void
    {
        $this->seedStatusData();

        $this->list(['jenis' => 'pelaksanaan', 'status' => 'sudah'])->assertOk()
            ->assertJsonPath('data', [['kode_pt' => (string) $this->ptA->npsn, 'nama_pt' => 'Universitas Alpha']]);

        $nama = collect($this->list(['jenis' => 'pelaksanaan', 'status' => 'belum'])->assertOk()->json('data'))->pluck('nama_pt')->all();
        $this->assertSame(['Institut 100% Beta', 'Politeknik Gamma'], $nama);
    }

    public function test_lokasi_sudah_uses_latest_year_desa_and_belum_has_null_wilayah(): void
    {
        $this->seedStatusData();

        $this->list(['jenis' => 'lokasi', 'status' => 'sudah'])->assertOk()
            ->assertJsonPath('data', [['nim' => '1110001', 'nama' => 'Andi', 'nama_pt' => 'Universitas Alpha', 'lokasi' => 'Kota Uji', 'kecamatan' => 'Kec Uji', 'desa' => 'Desa Baru']]);

        $data = $this->list(['jenis' => 'lokasi', 'status' => 'belum'])->assertOk()->json('data');
        $nama = array_column($data, 'nama');
        $this->assertEqualsCanonicalizing(['Budi_Dua', 'BudiXDua'], array_slice($nama, 0, 2));
        $this->assertSame(['Citra', null], array_slice($nama, 2));
        $this->assertNotContains('1110006', array_column($data, 'nim'));
        foreach ($data as $row) {
            $this->assertNull($row['kecamatan']);
            $this->assertNull($row['desa']);
            $this->assertSame('Kota Uji', $row['lokasi']);
        }
    }

    public function test_search_matches_nama_nim_and_nama_pt(): void
    {
        $this->seedStatusData();

        $nim = fn (array $p) => array_column($this->list($p + ['jenis' => 'lokasi', 'status' => 'belum'])->assertOk()->json('data'), 'nim');

        $this->assertSame(['1110005'], $nim(['q' => 'citr']));
        $this->assertSame(['1110004'], $nim(['q' => '1110004']));
        $this->assertSame(['1110003', '1110005', '1110004'], $nim(['q' => 'Beta']));
        $this->assertSame([], $nim(['q' => 'tidak-ada']));
        $this->assertSame(4, $this->list(['jenis' => 'lokasi', 'status' => 'belum', 'q' => '  '])->json('meta.total'));

        $this->assertSame(['1110001'], array_column($this->list(['jenis' => 'mobilisasi', 'status' => 'sudah', 'q' => 'alpha'])->json('data'), 'nim'));
        $this->assertSame(['Politeknik Gamma'], array_column($this->list(['jenis' => 'pelaksanaan', 'status' => 'belum', 'q' => 'gamma'])->json('data'), 'nama_pt'));
        $this->assertSame([(string) $this->ptA->npsn], array_column($this->list(['jenis' => 'pelaksanaan', 'status' => 'sudah', 'q' => (string) $this->ptA->npsn])->json('data'), 'kode_pt'));
    }

    public function test_search_treats_percent_and_underscore_literally(): void
    {
        $this->seedStatusData();

        $nama = fn (string $q) => array_column($this->list(['jenis' => 'lokasi', 'status' => 'belum', 'q' => $q])->assertOk()->json('data'), 'nama');

        $this->assertSame(['Budi_Dua'], $nama('Budi_'));
        $this->assertSame(['BudiXDua', 'Citra', null], $nama('100%'));
        $this->assertSame([], $nama('%'.'Alpha'.'%x'));
        $this->assertSame([], $nama('_'.'_'.'_'.'_'.'_'.'_'.'_'.'_'.'_'));
        // Two-char probes: as wildcards these would match (almost) every row
        $this->assertSame([], $nama('%%'));
        $this->assertSame([], $nama('__'));
        $this->assertSame(['Budi_Dua'], $nama('i_'));
        $this->assertSame(['BudiXDua', 'Citra', null], $nama('0%'));
        $this->assertSame([], $nama('_x'));
        $this->assertSame(['Institut 100% Beta'], array_column($this->list(['jenis' => 'pelaksanaan', 'status' => 'belum', 'q' => '0%'])->assertOk()->json('data'), 'nama_pt'));
    }

    public function test_single_char_query_is_rejected(): void
    {
        foreach (['a', '%', '_', '1'] as $q) {
            $this->list(['jenis' => 'lokasi', 'status' => 'belum', 'q' => $q])->assertStatus(422)->assertJsonValidationErrors('q');
        }
        $this->list(['jenis' => 'lokasi', 'status' => 'belum', 'q' => 'ab'])->assertOk();
    }

    public function test_pagination_meta_and_out_of_range_page(): void
    {
        $this->seedStatusData();
        $lokasi = LokasiProgram::first();
        Mahasiswa::factory()->count(27)->create(['kodept' => $this->ptA->npsn, 'location_program' => $lokasi->id]);

        $this->list(['jenis' => 'lokasi', 'status' => 'belum', 'per_page' => 25, 'page' => 2])->assertOk()
            ->assertJsonPath('meta', ['page' => 2, 'per_page' => 25, 'total' => 31, 'last_page' => 2, 'from' => 26, 'to' => 31, 'sort' => null, 'dir' => 'asc'])
            ->assertJsonCount(6, 'data');

        $last = $this->list(['jenis' => 'lokasi', 'status' => 'belum', 'per_page' => 25, 'page' => 2])->json('data');
        $this->assertNull(end($last)['nama']);

        $this->list(['jenis' => 'lokasi', 'status' => 'belum'])->assertOk()
            ->assertJsonPath('meta', ['page' => 1, 'per_page' => 10, 'total' => 31, 'last_page' => 4, 'from' => 1, 'to' => 10, 'sort' => null, 'dir' => 'asc'])
            ->assertJsonCount(10, 'data');

        $this->list(['jenis' => 'lokasi', 'status' => 'belum', 'page' => 99])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.page', 4)->assertJsonPath('meta.total', 31)
            ->assertJsonPath('meta.from', 31)->assertJsonPath('meta.to', 31);
    }

    public function test_page_beyond_last_page_is_clamped_to_last_page(): void
    {
        $this->seedStatusData();

        foreach (['mobilisasi', 'pelaksanaan', 'lokasi'] as $jenis) {
            $last = $this->list(['jenis' => $jenis, 'status' => 'belum'])->assertOk()->json('meta');
            $lastData = $this->list(['jenis' => $jenis, 'status' => 'belum', 'page' => $last['last_page']])->json('data');
            foreach ([$last['last_page'] + 1, 1001, 100000] as $page) {
                $this->list(['jenis' => $jenis, 'status' => 'belum', 'page' => $page])->assertOk()
                    ->assertJsonPath('data', $lastData)
                    ->assertJsonPath('meta.page', $last['last_page'])
                    ->assertJsonPath('meta.last_page', $last['last_page']);
            }
        }

        $this->list(['jenis' => 'lokasi', 'status' => 'belum', 'page' => 100001])->assertStatus(422)->assertJsonValidationErrors('page');
    }

    public function test_empty_population_returns_empty_list(): void
    {
        $this->list(['jenis' => 'mobilisasi', 'status' => 'sudah'])->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta', ['page' => 1, 'per_page' => 10, 'total' => 0, 'last_page' => 1, 'from' => null, 'to' => null, 'sort' => null, 'dir' => 'asc']);
    }

    private function column(array $params, string $field): array
    {
        return array_column($this->list($params)->assertOk()->json('data'), $field);
    }

    public function test_sort_lokasi_belum_asc_desc_with_nulls_last(): void
    {
        $this->seedStatusData();
        $p = ['jenis' => 'lokasi', 'status' => 'belum'];

        $this->list($p + ['sort' => 'nim', 'dir' => 'desc'])->assertOk()
            ->assertJsonPath('meta.sort', 'nim')->assertJsonPath('meta.dir', 'desc');
        $this->assertSame(['1110002', '1110003', '1110004', '1110005'], $this->column($p + ['sort' => 'nim'], 'nim'));
        $this->assertSame(['1110005', '1110004', '1110003', '1110002'], $this->column($p + ['sort' => 'nim', 'dir' => 'desc'], 'nim'));

        $asc = $this->column($p + ['sort' => 'nama', 'dir' => 'asc'], 'nama');
        $desc = $this->column($p + ['sort' => 'nama', 'dir' => 'desc'], 'nama');
        $this->assertSame('Citra', $asc[2]);
        $this->assertNull($asc[3]);
        $this->assertSame('Citra', $desc[0]);
        $this->assertNull($desc[3], 'NULL nama tetap terakhir saat desc');
        $this->assertSame(array_slice($asc, 0, 2), array_reverse(array_slice($desc, 1, 2)));

        $this->assertSame('1110002', $this->column($p + ['sort' => 'nama_pt'], 'nim')[3]);
        $this->assertSame('1110002', $this->column($p + ['sort' => 'nama_pt', 'dir' => 'desc'], 'nim')[0]);
        $this->assertSame(['Universitas Alpha', 'Institut 100% Beta', 'Institut 100% Beta', 'Institut 100% Beta'], $this->column($p + ['sort' => 'nama_pt', 'dir' => 'desc'], 'nama_pt'));
    }

    public function test_sort_lokasi_sudah_by_kecamatan_and_desa(): void
    {
        $this->seedStatusData();
        $kec = Kecamatan::factory()->create(['kecamatan' => 'Kec Abc']);
        $cempaka = Desa::factory()->create(['id_kecamatan' => $kec->id_kecamatan, 'desa' => 'Desa Cempaka']);
        $eka = Mahasiswa::factory()->create(['nama' => 'Eka', 'nim' => '1110007', 'kodept' => $this->ptA->npsn, 'location_program' => LokasiProgram::first()->id]);
        Mahasiswa_lokasi::create(['tahun' => 2026, 'id_mahasiswa' => $eka->id_mahasiswa, 'id_desa' => $cempaka->id_desa, 'user_in_up' => $eka->email]);
        $p = ['jenis' => 'lokasi', 'status' => 'sudah'];

        $this->assertSame(['Desa Baru', 'Desa Cempaka'], $this->column($p + ['sort' => 'desa'], 'desa'));
        $this->assertSame(['Desa Cempaka', 'Desa Baru'], $this->column($p + ['sort' => 'desa', 'dir' => 'desc'], 'desa'));
        $this->assertSame(['1110007', '1110001'], $this->column($p + ['sort' => 'kecamatan'], 'nim'));
        $this->assertSame(['1110001', '1110007'], $this->column($p + ['sort' => 'kecamatan', 'dir' => 'desc'], 'nim'));
        $this->assertEqualsCanonicalizing(['1110001', '1110007'], $this->column($p + ['sort' => 'lokasi', 'q' => '11100'], 'nim'));
    }

    public function test_sort_mobilisasi_and_pelaksanaan_keep_nulls_last(): void
    {
        $this->seedStatusData();
        $p = ['jenis' => 'mobilisasi', 'status' => 'belum'];

        foreach (['nim', 'nama', 'nama_pt'] as $field) {
            foreach (['asc', 'desc'] as $dir) {
                $values = $this->column($p + ['sort' => $field, 'dir' => $dir], $field);
                $this->assertNotNull($values[0], "$field $dir");
                $this->assertSame([null, null], array_slice($values, 1), "$field $dir: NULL terakhir");
            }
        }
        $this->assertSame(['Desa Baru', 'Desa Baru', 'Desa Baru'], $this->column($p + ['sort' => 'desa', 'dir' => 'desc'], 'desa'));
        $this->assertSame(['Kec Uji', 'Kec Uji', 'Kec Uji'], $this->column($p + ['sort' => 'kecamatan'], 'kecamatan'));

        $q = ['jenis' => 'pelaksanaan', 'status' => 'belum'];
        $this->assertSame(['Institut 100% Beta', 'Politeknik Gamma'], $this->column($q + ['sort' => 'nama_pt'], 'nama_pt'));
        $this->assertSame(['Politeknik Gamma', 'Institut 100% Beta'], $this->column($q + ['sort' => 'nama_pt', 'dir' => 'desc'], 'nama_pt'));
        $kode = $this->column($q + ['sort' => 'kode_pt'], 'kode_pt');
        $sorted = $kode;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $kode);
        $this->assertSame(array_reverse($kode), $this->column($q + ['sort' => 'kode_pt', 'dir' => 'desc'], 'kode_pt'));
    }

    public function test_sort_pagination_is_stable_on_tied_values(): void
    {
        $this->seedStatusData();
        Mahasiswa::factory()->count(27)->create(['kodept' => $this->ptA->npsn, 'location_program' => LokasiProgram::first()->id]);
        $p = ['jenis' => 'lokasi', 'status' => 'belum', 'per_page' => 10];

        foreach ([['lokasi', 'asc'], ['nama_pt', 'desc']] as [$sort, $dir]) {
            $nims = [];
            for ($page = 1; $page <= 4; $page++) {
                $nims = array_merge($nims, $this->column($p + ['sort' => $sort, 'dir' => $dir, 'page' => $page], 'nim'));
            }
            $this->assertCount(31, $nims, "$sort $dir");
            $this->assertCount(31, array_unique($nims), "$sort $dir: halaman tumpang tindih");
        }
    }

    public function test_invalid_sort_or_dir_returns_422(): void
    {
        $cases = [
            [['jenis' => 'lokasi', 'sort' => 'kode_pt'], 'sort'],
            [['jenis' => 'pelaksanaan', 'sort' => 'nim'], 'sort'],
            [['jenis' => 'mobilisasi', 'sort' => 'lokasi'], 'sort'],
            [['jenis' => 'lokasi', 'sort' => 'nama; DROP TABLE mahasiswa'], 'sort'],
            [['jenis' => 'lokasi', 'sort' => 'm.nama'], 'sort'],
            [['jenis' => 'lokasi', 'sort' => 'email'], 'sort'],
            [['jenis' => 'lokasi', 'sort' => ['nama']], 'sort'],
            [['jenis' => 'lokasi', 'sort' => 'nama', 'dir' => 'up'], 'dir'],
            [['jenis' => 'lokasi', 'sort' => 'nama', 'dir' => 'DESC'], 'dir'],
            [['jenis' => 'lokasi', 'sort' => 'nama', 'dir' => 'asc, nim'], 'dir'],
            [['jenis' => 'lokasi', 'dir' => ['desc']], 'dir'],
        ];
        foreach ($cases as [$params, $field]) {
            $this->list($params + ['status' => 'sudah'])->assertStatus(422)->assertJsonValidationErrors($field);
        }
        $this->list(['jenis' => 'pelaksanaan', 'status' => 'sudah', 'sort' => 'kode_pt', 'dir' => 'desc'])->assertOk();
    }

    public function test_invalid_params_return_422(): void
    {
        $cases = [
            [['status' => 'sudah'], 'jenis'],
            [['jenis' => 'tidak-ada', 'status' => 'sudah'], 'jenis'],
            [['jenis' => 'lokasi'], 'status'],
            [['jenis' => 'lokasi', 'status' => 'semua'], 'status'],
            [['jenis' => 'lokasi', 'status' => 'sudah', 'per_page' => 100], 'per_page'],
            [['jenis' => 'lokasi', 'status' => 'sudah', 'per_page' => 'abc'], 'per_page'],
            [['jenis' => 'lokasi', 'status' => 'sudah', 'q' => str_repeat('a', 51)], 'q'],
            [['jenis' => 'lokasi', 'status' => 'sudah', 'q' => ['x']], 'q'],
            [['jenis' => 'lokasi', 'status' => 'sudah', 'page' => 0], 'page'],
            [['jenis' => 'lokasi', 'status' => 'sudah', 'page' => 'x'], 'page'],
            [['jenis' => 'lokasi', 'status' => 'sudah', 'page' => 100001], 'page'],
            [['jenis' => 'lokasi', 'status' => 'sudah', 'q' => 'a'], 'q'],
        ];
        foreach ($cases as [$params, $field]) {
            $this->list($params)->assertStatus(422)->assertJsonValidationErrors($field);
        }

        $this->list(['jenis' => 'lokasi', 'status' => 'sudah', 'q' => str_repeat('a', 50)])->assertOk();
    }

    // A: ketua+mhs+DPL (sudah); B: mhs; C: DPL only; N: nm_lemb NULL; 0999999: no ref row; D: no lokasi (excluded)
    private function seedKetuaData(): array
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Uji'])->id;
        $a = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alpha']);
        $b = Satuanpendidikan::factory()->create(['nm_lemb' => 'Institut Beta']);
        $c = Satuanpendidikan::factory()->create(['nm_lemb' => 'Politeknik Gamma']);
        $n = Satuanpendidikan::factory()->create(['nm_lemb' => null, 'npsn' => '0300001']);
        $d = Satuanpendidikan::factory()->create(['nm_lemb' => 'Akademi Delta']);
        $desa = Desa::factory()->create()->id_desa;

        $people = [
            $ketua = Mahasiswa::factory()->create(['kodept' => $a->npsn, 'location_program' => $lokasi, 'email' => 'ketua@pps.test']),
            Mahasiswa::factory()->create(['kodept' => $a->npsn, 'location_program' => $lokasi]),
            Dpl::factory()->create(['kodept' => $a->npsn, 'location_program' => $lokasi]),
            Mahasiswa::factory()->create(['kodept' => $b->npsn, 'location_program' => $lokasi]),
            Dpl::factory()->create(['kodept' => $c->npsn, 'location_program' => $lokasi]),
            Mahasiswa::factory()->create(['kodept' => $n->npsn, 'location_program' => $lokasi]),
            Mahasiswa::factory()->create(['kodept' => '0999999', 'location_program' => $lokasi]),
            $tanpaLokasi = Mahasiswa::factory()->create(['kodept' => $d->npsn]),
            Dpl::factory()->create(['kodept' => $d->npsn]),
        ];
        Pjdesa::create(['email' => $ketua->email, 'id_desa' => $desa, 'status_mobilisasi' => 'sudah']);
        Pjdesa::create(['email' => $tanpaLokasi->email, 'id_desa' => $desa]);

        return [$a, $b, $c, $n, $d, $people];
    }

    public function test_ketua_totals_match_status_ketua_and_rows_are_correct(): void
    {
        [$a, $b, $c] = $this->seedKetuaData();
        $expected = app(SebaranPtSummary::class)->statusKetua();
        $this->assertSame(['sudah' => 1, 'belum' => 4, 'total' => 5], $expected);

        $rows = [];
        foreach (['sudah', 'belum'] as $status) {
            $res = $this->list(['jenis' => 'ketua', 'status' => $status, 'per_page' => 25])->assertOk()
                ->assertJsonPath('jenis', 'ketua')->assertJsonPath('status', $status)
                ->assertJsonPath('meta.sort', null)->assertJsonPath('meta.dir', 'asc');
            $this->assertSame($expected[$status], $res->json('meta.total'), "ketua/$status");
            $this->assertCount($expected[$status], $res->json('data'));
            $rows[$status] = $res->json('data');
        }

        $this->assertSame([['kode_pt' => (string) $a->npsn, 'nama_pt' => 'Universitas Alpha', 'jumlah_ketua' => 1]], $rows['sudah']);
        // Default sort nama_pt asc; nama_pt falls back to kodept (NULL nm_lemb / no ref row); C is DPL-only
        $this->assertSame([
            ['kode_pt' => '0300001', 'nama_pt' => '0300001', 'jumlah_ketua' => 0],
            ['kode_pt' => '0999999', 'nama_pt' => '0999999', 'jumlah_ketua' => 0],
            ['kode_pt' => (string) $b->npsn, 'nama_pt' => 'Institut Beta', 'jumlah_ketua' => 0],
            ['kode_pt' => (string) $c->npsn, 'nama_pt' => 'Politeknik Gamma', 'jumlah_ketua' => 0],
        ], $rows['belum']);
        $this->assertNotContains('Akademi Delta', array_column(array_merge(...array_values($rows)), 'nama_pt'));
    }

    public function test_ketua_moves_to_sudah_after_pjdesa_added(): void
    {
        [, $b] = $this->seedKetuaData();
        $mhs = Mahasiswa::where('kodept', $b->npsn)->firstOrFail();
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        $this->assertSame(['Institut Beta', 'Universitas Alpha'], $this->column(['jenis' => 'ketua', 'status' => 'sudah'], 'nama_pt'));
        $this->assertSame(3, $this->list(['jenis' => 'ketua', 'status' => 'belum'])->json('meta.total'));
    }

    public function test_ketua_response_exposes_no_personal_data(): void
    {
        [, , , , , $people] = $this->seedKetuaData();

        foreach (['sudah', 'belum'] as $status) {
            $res = $this->list(['jenis' => 'ketua', 'status' => $status, 'per_page' => 25])->assertOk();
            foreach ($res->json('data') as $row) {
                $this->assertSame(['kode_pt', 'nama_pt', 'jumlah_ketua'], array_keys($row));
                $this->assertIsInt($row['jumlah_ketua']);
            }
            $raw = $res->getContent();
            $this->assertDoesNotMatchRegularExpression('/"(email|phone|no_hp|nim|nidn|nama|id|id_[a-z_]+|kodept)"\s*:/', $raw);
            foreach ($people as $person) {
                foreach (['email', 'phone', 'nim', 'nidn', 'nama'] as $field) {
                    if (filled($person->{$field} ?? null)) {
                        $this->assertStringNotContainsString((string) $person->{$field}, $raw, "ketua/$status bocor $field");
                    }
                }
            }
        }
    }

    public function test_ketua_search_and_sort_whitelist(): void
    {
        [$a, $b, $c] = $this->seedKetuaData();
        $belum = ['jenis' => 'ketua', 'status' => 'belum'];

        $this->assertSame(['Politeknik Gamma'], $this->column($belum + ['q' => 'gamma'], 'nama_pt'));
        $this->assertSame(['0999999'], $this->column($belum + ['q' => '09999'], 'kode_pt'));
        $this->assertSame([(string) $c->npsn], $this->column($belum + ['q' => (string) $c->npsn], 'kode_pt'));
        $this->assertSame([], $this->column($belum + ['q' => 'alpha'], 'kode_pt'));
        $this->assertSame([(string) $a->npsn], $this->column(['jenis' => 'ketua', 'status' => 'sudah', 'q' => 'alpha'], 'kode_pt'));
        $this->assertSame([], $this->column($belum + ['q' => '%%'], 'kode_pt'));
        $this->list($belum + ['q' => 'g'])->assertStatus(422)->assertJsonValidationErrors('q');

        $kode = $this->column($belum + ['sort' => 'kode_pt'], 'kode_pt');
        $sorted = $kode;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $kode);
        $this->assertSame(array_reverse($kode), $this->column($belum + ['sort' => 'kode_pt', 'dir' => 'desc'], 'kode_pt'));
        $this->assertSame(['Politeknik Gamma', 'Institut Beta', '0999999', '0300001'], $this->column($belum + ['sort' => 'nama_pt', 'dir' => 'desc'], 'nama_pt'));

        foreach (['nim', 'nama', 'lokasi', 'kodept', 'email', 'nama_pt; DROP TABLE mahasiswa'] as $sort) {
            $this->list($belum + ['sort' => $sort])->assertStatus(422)->assertJsonValidationErrors('sort');
        }
        $this->list($belum + ['sort' => 'nama_pt', 'dir' => 'DESC'])->assertStatus(422)->assertJsonValidationErrors('dir');
    }

    // A: 2 distinct ketua (+ duplicate mahasiswa & pj_desa rows for one email); B: 1 ketua without lokasi
    private function seedKetuaCounts(): array
    {
        [$a, $b, $c, $n, $d] = $this->seedKetuaData();
        $lokasi = LokasiProgram::first()->id;
        $desa = Desa::factory()->create()->id_desa;

        $ketua2 = Mahasiswa::factory()->create(['kodept' => $a->npsn, 'location_program' => $lokasi]);
        Mahasiswa::factory()->create(['kodept' => $a->npsn, 'location_program' => $lokasi, 'email' => 'ketua@pps.test']);
        Pjdesa::create(['email' => 'ketua@pps.test', 'id_desa' => $desa]);
        Pjdesa::create(['email' => $ketua2->email, 'id_desa' => $desa]);
        $ketuaB = Mahasiswa::factory()->create(['kodept' => $b->npsn]);
        Pjdesa::create(['email' => $ketuaB->email, 'id_desa' => $desa]);

        return [$a, $b, $c, $n, $d];
    }

    public function test_ketua_jumlah_ketua_counts_distinct_email_and_matches_data_peserta_summary(): void
    {
        [$a, $b] = $this->seedKetuaCounts();

        $res = $this->list(['jenis' => 'ketua', 'status' => 'sudah', 'per_page' => 25])->assertOk();
        $jumlah = array_column($res->json('data'), 'jumlah_ketua', 'kode_pt');
        $this->assertSame([(string) $b->npsn => 1, (string) $a->npsn => 2], $jumlah);
        $this->assertSame(2, $res->json('meta.total'));

        $summary = app(DataPesertaSummary::class)->all()->pluck('jumlah_ketua_kelompok', 'kodept');
        foreach ($jumlah as $kode => $total) {
            $this->assertSame($summary[$kode], $total, "jumlah_ketua $kode != jumlah_ketua_kelompok");
        }
    }

    public function test_ketua_belum_rows_always_have_zero_jumlah_ketua(): void
    {
        $this->seedKetuaCounts();
        $belum = ['jenis' => 'ketua', 'status' => 'belum', 'per_page' => 25];

        foreach ([[], ['sort' => 'jumlah_ketua'], ['sort' => 'jumlah_ketua', 'dir' => 'desc']] as $extra) {
            $res = $this->list($belum + $extra)->assertOk();
            $this->assertSame(3, $res->json('meta.total'));
            $this->assertSame([0, 0, 0], array_column($res->json('data'), 'jumlah_ketua'));
        }
    }

    public function test_ketua_sort_by_jumlah_ketua_asc_desc(): void
    {
        [$a, $b, $c] = $this->seedKetuaCounts();
        $desa = Desa::factory()->create()->id_desa;
        // C gets 3 ketua (DPL-only PT moves to sudah)
        foreach (range(1, 3) as $i) {
            Pjdesa::create(['email' => Mahasiswa::factory()->create(['kodept' => $c->npsn])->email, 'id_desa' => $desa]);
        }
        $sudah = ['jenis' => 'ketua', 'status' => 'sudah'];

        $asc = $this->list($sudah + ['sort' => 'jumlah_ketua'])->assertOk()
            ->assertJsonPath('meta.sort', 'jumlah_ketua')->assertJsonPath('meta.dir', 'asc');
        $this->assertSame([1, 2, 3], array_column($asc->json('data'), 'jumlah_ketua'));
        $this->assertSame([(string) $b->npsn, (string) $a->npsn, (string) $c->npsn], array_column($asc->json('data'), 'kode_pt'));

        $desc = $this->list($sudah + ['sort' => 'jumlah_ketua', 'dir' => 'desc'])->assertOk()
            ->assertJsonPath('meta.sort', 'jumlah_ketua')->assertJsonPath('meta.dir', 'desc');
        $this->assertSame([3, 2, 1], array_column($desc->json('data'), 'jumlah_ketua'));
        $this->assertSame([(string) $c->npsn, (string) $a->npsn, (string) $b->npsn], array_column($desc->json('data'), 'kode_pt'));

        // jumlah_ketua is ketua-only
        foreach (['mobilisasi', 'pelaksanaan', 'lokasi'] as $jenis) {
            $this->list(['jenis' => $jenis, 'status' => 'sudah', 'sort' => 'jumlah_ketua'])->assertStatus(422)->assertJsonValidationErrors('sort');
        }
    }

    public function test_ketua_sort_by_jumlah_ketua_ties_break_by_kode_pt(): void
    {
        [$a, , $c] = $this->seedKetuaCounts();
        $desa = Desa::factory()->create()->id_desa;
        // C ties with A at 2 ketua
        foreach (range(1, 2) as $i) {
            Pjdesa::create(['email' => Mahasiswa::factory()->create(['kodept' => $c->npsn])->email, 'id_desa' => $desa]);
        }
        $tied = [(string) $a->npsn, (string) $c->npsn];
        sort($tied, SORT_STRING);

        foreach (['asc', 'desc'] as $dir) {
            $rows = $this->list(['jenis' => 'ketua', 'status' => 'sudah', 'sort' => 'jumlah_ketua', 'dir' => $dir])->assertOk()->json('data');
            $twos = array_values(array_filter($rows, fn ($r) => $r['jumlah_ketua'] === 2));
            $this->assertSame($tied, array_column($twos, 'kode_pt'), "tie order $dir");
        }
    }

    public function test_ketua_pagination(): void
    {
        $this->seedKetuaData();
        $belum = ['jenis' => 'ketua', 'status' => 'belum'];
        $lokasi = LokasiProgram::first()->id;
        foreach (Satuanpendidikan::factory()->count(27)->create() as $pt) {
            Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $lokasi]);
        }

        $this->list($belum + ['per_page' => 25, 'page' => 2])->assertOk()
            ->assertJsonPath('meta', ['page' => 2, 'per_page' => 25, 'total' => 31, 'last_page' => 2, 'from' => 26, 'to' => 31, 'sort' => null, 'dir' => 'asc'])
            ->assertJsonCount(6, 'data');
        $this->list($belum + ['page' => 99])->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('meta.page', 4)->assertJsonPath('meta.last_page', 4);

        $all = [];
        for ($page = 1; $page <= 4; $page++) {
            $all = array_merge($all, $this->column($belum + ['sort' => 'kode_pt', 'page' => $page], 'kode_pt'));
        }
        $this->assertCount(31, array_unique($all));
        $this->assertSame(31, app(SebaranPtSummary::class)->statusKetua()['belum']);
    }

    public function test_route_is_public_for_guest_and_authenticated_user(): void
    {
        $this->assertSame(url('login/status-list'), route('login.status-list'));
        $this->list(['jenis' => 'mobilisasi', 'status' => 'belum'])->assertOk();

        foreach (['admin', 'pt', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            $this->list(['jenis' => 'lokasi', 'status' => 'belum'])->assertOk();
        }
    }

    public function test_throttled_on_21st_request_per_minute_without_touching_peta_bucket(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->list(['jenis' => 'pelaksanaan', 'status' => 'sudah'])->assertOk();
        }
        $this->list(['jenis' => 'pelaksanaan', 'status' => 'sudah'])->assertStatus(429);

        // peta bucket still has its full 30/min after status-list is exhausted
        for ($i = 0; $i < 30; $i++) {
            $this->getJson(route('login.peserta'))->assertStatus(422);
        }
        $this->getJson(route('login.peserta'))->assertStatus(429);
    }

    public function test_peta_bucket_exhaustion_does_not_block_status_list(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->getJson(route('login.peserta'))->assertStatus(422);
        }
        $this->getJson(route('login.peserta'))->assertStatus(429);

        $this->list(['jenis' => 'pelaksanaan', 'status' => 'sudah'])->assertOk();
    }

    public function test_hourly_limit_of_200_requests(): void
    {
        for ($minute = 0; $minute < 10; $minute++) {
            $this->travel(61)->seconds();
            for ($i = 0; $i < 20; $i++) {
                $this->list(['jenis' => 'pelaksanaan', 'status' => 'sudah'])->assertOk();
            }
        }

        $this->travel(61)->seconds();
        $this->list(['jenis' => 'pelaksanaan', 'status' => 'sudah'])->assertStatus(429);
    }
}
