<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisteredTotalsConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private const PT_A = '041001';

    private const PT_B = '042002';

    private const PT_DPL_ONLY = '043003';

    protected function setUp(): void
    {
        parent::setUp();

        $kec = Kecamatan::factory()->create();
        [$d1, $d2] = Desa::factory()->count(2)->create(['id_kecamatan' => $kec->id_kecamatan]);
        Desa::factory()->create(); // master-only desa + kecamatan, no placement
        $lokasi = LokasiProgram::factory()->create();

        $this->placed(self::PT_A, $lokasi, $d1);
        $this->placed(self::PT_A, $lokasi, $d2);
        Mahasiswa::factory()->create(['kodept' => self::PT_A, 'location_program' => $lokasi->id]); // unplaced
        $this->placed(self::PT_B, $lokasi, $d1);
        Mahasiswa::factory()->create(); // no PT, unplaced

        Dpl::factory()->create(['kodept' => self::PT_A, 'location_program' => $lokasi->id]);
        Dpl::factory()->create(['kodept' => self::PT_B]); // no lokasi
        Dpl::factory()->create(['kodept' => self::PT_DPL_ONLY, 'location_program' => $lokasi->id]);
        Dpl::factory()->create(); // no PT
    }

    private function placed(string $kodept, LokasiProgram $lokasi, Desa $desa): void
    {
        $mhs = Mahasiswa::factory()->create(['kodept' => $kodept, 'location_program' => $lokasi->id]);
        Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $desa->id_desa]);
    }

    private static function assertRowsPlusBelumEqualTotal(array $lokasi): void
    {
        self::assertSame($lokasi['total']['mahasiswa'], $lokasi['rows']->sum('mahasiswa') + $lokasi['belum_lokasi']);
        self::assertSame($lokasi['total']['dpl'], $lokasi['rows']->sum('dpl') + $lokasi['belum_lokasi_dpl']);
    }

    public function test_login_stats_match_admin_cards_and_lokasi_total(): void
    {
        $expected = [
            'pt' => 3,
            'mahasiswa' => 5,
            'dpl' => 4,
            'kecamatan' => Kecamatan::count(),
            'kelurahan' => Desa::count(),
        ];

        $this->get(route('login'))->assertOk()
            ->assertViewHas('jumlahPt', $expected['pt'])
            ->assertViewHas('jumlahMahasiswa', $expected['mahasiswa'])
            ->assertViewHas('jumlahDpl', $expected['dpl'])
            ->assertViewHas('jumlahKecamatan', $expected['kecamatan'])
            ->assertViewHas('jumlahKelurahan', $expected['kelurahan']);

        $this->loginAs('admin');
        $response = $this->get('home')->assertOk()
            ->assertViewHas('jumlahpt', $expected['pt'])
            ->assertViewHas('jumlahmahasiswa', $expected['mahasiswa'])
            ->assertViewHas('jumlahdpl', $expected['dpl']);

        $lokasi = $response->viewData('penguranganSampahHome')['lokasi'];
        $this->assertEquals($expected, collect($lokasi['total'])->except('kelompok')->all());
        self::assertRowsPlusBelumEqualTotal($lokasi);
        // DPL without lokasi lands in 'Tanpa Lokasi Program' row; DPL without PT in belum
        $this->assertSame([2, 1], [$lokasi['belum_lokasi'], $lokasi['belum_lokasi_dpl']]);
        $this->assertSame(1, $lokasi['rows']->firstWhere('lokasi', 'Tanpa Lokasi Program')['dpl']);
    }

    public function test_pt_totals_are_scoped_to_own_kodept(): void
    {
        $this->loginAs('pt', ['email' => self::PT_A]);

        $response = $this->get('home')->assertOk()
            ->assertViewHas('jumlahmahasiswa', 3)
            ->assertViewHas('jumlahdpl', 1);

        $lokasi = $response->viewData('penguranganSampahHome')['lokasi'];
        $this->assertEquals(['pt' => 1, 'mahasiswa' => 3, 'dpl' => 1, 'kecamatan' => 1, 'kelurahan' => 2],
            collect($lokasi['total'])->except('kelompok')->all());
        self::assertRowsPlusBelumEqualTotal($lokasi);
        $this->assertSame([1, 0], [$lokasi['belum_lokasi'], $lokasi['belum_lokasi_dpl']]);
    }

    public function test_pt_with_no_data_gets_zero_totals(): void
    {
        $this->loginAs('pt', ['email' => '099999']);

        $lokasi = $this->get('home')->assertOk()->viewData('penguranganSampahHome')['lokasi'];

        $this->assertEquals(['pt' => 0, 'mahasiswa' => 0, 'dpl' => 0, 'kecamatan' => 0, 'kelurahan' => 0, 'kelompok' => 0], $lokasi['total']);
        $this->assertSame([0, 0], [$lokasi['belum_lokasi'], $lokasi['belum_lokasi_dpl']]);
    }

    public function test_guest_cannot_open_dashboard(): void
    {
        $this->get('home')->assertRedirect(route('login'));
    }
}
