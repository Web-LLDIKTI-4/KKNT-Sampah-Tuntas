<?php

namespace Tests\Feature;

use App\Exports\Sheets\RekapLldiktiSheet;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\PenguranganSampah;
use App\Models\Logkegiatan;
use App\Models\PendataanPemilahanSampah;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Services\PenguranganSampahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class RekapSampahLogHarianTest extends TestCase
{
    use RefreshDatabase;

    private const BULAN = '2026-09';

    private function mahasiswaDi(Desa $desa, ?string $kodept = null, ?int $tahun = null): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create(['kodept' => $kodept]);
        Mahasiswa_lokasi::create(['tahun' => $tahun ?? (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $desa->id_desa, 'user_in_up' => $mhs->email]);

        return $mhs;
    }

    private function log(Mahasiswa $mhs, array $attr = []): PendataanPemilahanSampah
    {
        return PendataanPemilahanSampah::factory()->create($attr + [
            'email' => $mhs->email, 'tanggal' => self::BULAN.'-10',
            'nama_kepala_keluarga' => 'Budi', 'alamat_rumah' => 'Jl. Melati 1', 'rt' => '001', 'rw' => '002',
            'memilah' => true, 'organik_kg' => 1, 'anorganik_kg' => 1, 'residu_kg' => 1,
        ]);
    }

    private function rekap(array $filter = []): Collection
    {
        return app(PenguranganSampahService::class)->rekapLldikti($filter);
    }

    private function barisDesa($rekap, string $bulan, string $idDesa): object
    {
        return $rekap->firstWhere('bulan', $bulan)->kecamatan->flatMap->desa->firstWhere('id_desa', $idDesa);
    }

    public function test_jumlah_rumah_distinct_memilah_dan_kolom_g_sampai_l(): void
    {
        $desa = Desa::factory()->create();
        [$a, $b] = [$this->mahasiswaDi($desa), $this->mahasiswaDi($desa)];

        // Rumah Budi dilog 3x oleh 2 mahasiswa → D tetap 1; memilah sekali saja sudah dihitung di E
        $this->log($a, ['memilah' => false, 'organik_kg' => 2, 'anorganik_kg' => 1, 'residu_kg' => 1]);
        $this->log($a, ['tanggal' => self::BULAN.'-11', 'organik_kg' => 1.5, 'anorganik_kg' => 0.5, 'residu_kg' => 0]);
        $this->log($b, ['tanggal' => self::BULAN.'-12', 'organik_kg' => 0, 'anorganik_kg' => 0, 'residu_kg' => 2]);
        // RT beda = rumah beda, tidak memilah
        $this->log($b, ['rt' => '009', 'memilah' => false, 'organik_kg' => 0, 'anorganik_kg' => 0, 'residu_kg' => 4]);

        $r = $this->barisDesa($this->rekap(), self::BULAN, $desa->id_desa);

        $this->assertSame(2, $r->jml_rumah);
        $this->assertSame(1, $r->jml_rumah_memilah);
        $this->assertEquals(50.0, $r->persen_ketaatan);
        $this->assertEquals(3.5, $r->organik);
        $this->assertEquals(1.5, $r->anorganik);
        $this->assertEquals(7.0, $r->residu);
        $this->assertEquals(5.0, $r->total_terkelola);
        $this->assertEquals(12.0, $r->total_dihasilkan);
        $this->assertEqualsWithDelta(41.67, $r->persen_penurunan, 0.01);
        $this->assertSame($r->persen_penurunan, $r->persen_pengurangan);
    }

    public function test_pembagian_nol_menghasilkan_null_dan_tampil_strip(): void
    {
        $desa = Desa::factory()->create(['desa' => 'Desa Nol']);
        $this->log($this->mahasiswaDi($desa), ['memilah' => false, 'organik_kg' => 0, 'anorganik_kg' => 0, 'residu_kg' => 0]);

        $r = $this->barisDesa($this->rekap(), self::BULAN, $desa->id_desa);
        $this->assertSame(1, $r->jml_rumah);
        $this->assertEquals(0.0, $r->persen_ketaatan);
        $this->assertNull($r->persen_penurunan);

        $html = view('rekapsampah._lldikti', ['rekap' => $this->rekap()])->render();
        $this->assertStringContainsString('Desa Nol', $html);
        $this->assertMatchesRegularExpression('/fw-medium">-<\/td>/', $html);

        $sheet = new RekapLldiktiSheet($this->rekap());
        $this->assertSame('-', $sheet->array()[1][11]);
        $this->assertEquals(0.0, $sheet->array()[1][5]);
    }

    public function test_rekap_menggunakan_pendataan_bukan_log_kegiatan(): void
    {
        $desa = Desa::factory()->create();
        $mhs = $this->mahasiswaDi($desa);
        Logkegiatan::factory()->create([
            'email' => $mhs->email,
            'tanggal' => self::BULAN.'-10',
            'nama_kepala_keluarga' => 'Budi',
            'alamat_rumah' => 'Jl. Melati',
            'rt' => '001',
            'rw' => '001',
            'memilah' => true,
            'organik_kg' => 10,
            'anorganik_kg' => 0,
            'residu_kg' => 0,
        ]);

        $this->assertTrue($this->rekap()->isEmpty());

        $this->log($mhs, ['organik_kg' => 3, 'anorganik_kg' => 0, 'residu_kg' => 1]);
        $this->assertEquals(3.0, $this->barisDesa($this->rekap(), self::BULAN, $desa->id_desa)->organik);
    }

    public function test_grup_bulan_kecamatan_desa_dan_lokasi_terbaru(): void
    {
        $kecA = Kecamatan::factory()->create(['kecamatan' => 'Kec A']);
        $kecB = Kecamatan::factory()->create(['kecamatan' => 'Kec B']);
        $desa1 = Desa::factory()->create(['id_kecamatan' => $kecA->id_kecamatan, 'desa' => 'Desa 1']);
        $desa2 = Desa::factory()->create(['id_kecamatan' => $kecA->id_kecamatan, 'desa' => 'Desa 2']);
        $desa3 = Desa::factory()->create(['id_kecamatan' => $kecB->id_kecamatan, 'desa' => 'Desa 3']);

        $this->log($this->mahasiswaDi($desa1));
        $this->log($this->mahasiswaDi($desa2));
        $this->log($this->mahasiswaDi($desa3), ['tanggal' => '2026-08-31']);

        // Lokasi tahun lalu di desa1, tahun ini di desa3 → ikut desa3
        $pindah = $this->mahasiswaDi($desa1, null, (int) date('Y') - 1);
        Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $pindah->id_mahasiswa, 'id_desa' => $desa3->id_desa, 'user_in_up' => 'x']);
        $this->log($pindah, ['nama_kepala_keluarga' => 'Pindah']);

        // Tanpa mahasiswa_lokasi → tidak masuk rekap
        $tanpaLokasi = Mahasiswa::factory()->create();
        $this->log($tanpaLokasi, ['organik_kg' => 100]);

        $rekap = $this->rekap();

        $this->assertSame(['2026-09', '2026-08'], $rekap->pluck('bulan')->all());
        $this->assertSame(['September 2026', 'Agustus 2026'], $rekap->pluck('nama_bulan')->all());
        $sep = $rekap->first();
        $this->assertSame(['Kec A', 'Kec B'], $sep->kecamatan->pluck('kecamatan')->all());
        $this->assertSame(['Desa 1', 'Desa 2'], $sep->kecamatan[0]->desa->pluck('desa')->all());
        $this->assertSame(['Desa 3'], $sep->kecamatan[1]->desa->pluck('desa')->all());
        $this->assertSame(1, $sep->kecamatan[0]->desa[0]->jml_rumah);
        $this->assertEquals(3.0, $sep->kecamatan->flatMap->desa->sum('organik'));
        // Desa 3 bulan September hanya berisi log mahasiswa pindahan
        $this->assertSame(1, $sep->kecamatan[1]->desa[0]->jml_rumah);

        $total = app(PenguranganSampahService::class)->total(['bulan' => self::BULAN]);
        $this->assertSame(3, (int) $total->jml_desa);
        $this->assertEquals(3.0, $total->organik);

        // Filter bulan & desa
        $this->assertSame(['2026-08'], $this->rekap(['bulan' => '2026-08'])->pluck('bulan')->all());
        $this->assertSame(['2026-08', '2026-09'], $this->rekap(['id_desa' => $desa3->id_desa])->pluck('bulan')->sort()->values()->all());
    }

    public function test_bulan_sama_beda_tahun_tidak_tercampur(): void
    {
        $desa = Desa::factory()->create(['desa' => 'Desa Lintas']);
        $mhs = $this->mahasiswaDi($desa);
        $this->log($mhs, ['tanggal' => '2025-09-10', 'organik_kg' => 5]);
        $this->log($mhs, ['tanggal' => '2026-09-10', 'organik_kg' => 2]);

        $rekap = $this->rekap();
        $this->assertSame(['2026-09', '2025-09'], $rekap->pluck('bulan')->all());
        $this->assertSame(['September 2026', 'September 2025'], $rekap->pluck('nama_bulan')->all());
        $this->assertEquals(2.0, $this->barisDesa($rekap, '2026-09', $desa->id_desa)->organik);
        $this->assertEquals(5.0, $this->barisDesa($rekap, '2025-09', $desa->id_desa)->organik);

        $html = view('rekapsampah._lldikti', ['rekap' => $rekap])->render();
        $this->assertStringContainsString('September 2026', $html);
        $this->assertStringContainsString('September 2025', $html);

        $kolomA = array_column((new RekapLldiktiSheet($rekap))->array(), 0);
        $this->assertContains('September 2026', $kolomA);
        $this->assertContains('September 2025', $kolomA);
    }

    public function test_export_rekap_lldikti_header_dan_merge(): void
    {
        $kec = Kecamatan::factory()->create(['kecamatan' => 'Kec A']);
        foreach (['Desa 1', 'Desa 2'] as $nama) {
            $this->log($this->mahasiswaDi(Desa::factory()->create(['id_kecamatan' => $kec->id_kecamatan, 'desa' => $nama])));
        }

        $path = tempnam(sys_get_temp_dir(), 'rekap').'.xlsx';
        file_put_contents($path, Excel::raw(new RekapLldiktiSheet($this->rekap()), ExcelType::XLSX));
        $ws = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        $this->assertSame(RekapLldiktiSheet::HEADER, $ws->rangeToArray('A1:L1')[0]);
        $this->assertSame('Persentase Penurunan Sampah [(J/K)*100%]', $ws->getCell('L1')->getValue());
        $this->assertSame('September 2026', $ws->getCell('A2')->getValue());
        $this->assertContains('A2:A3', array_values($ws->getMergeCells()));
        $this->assertContains('B2:B3', array_values($ws->getMergeCells()));
        $this->assertSame('FFFF00', $ws->getStyle('A1')->getFill()->getStartColor()->getRGB());
        $this->assertTrue($ws->getStyle('A1')->getFont()->getBold());
        $this->assertSame('0.00%', $ws->getStyle('L2')->getNumberFormat()->getFormatCode());
    }

    public function test_view_rekapsampah_dashboard_dan_capaiankegiatan_render_tanpa_error(): void
    {
        $pt = Satuanpendidikan::factory()->create();
        $desa = Desa::factory()->create(['desa' => 'Desa Render']);
        $this->log($this->mahasiswaDi($desa, $pt->npsn));

        foreach (['admin', 'kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $this->get('rekapsampah?bulan=semua')->assertOk()->assertViewHas('rekap')->assertSee('Desa Render')
                ->assertSee('Jumlah Rumah yang memilah');
            $this->get('rekapsampah?bulan='.self::BULAN, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertSee('Desa Render');
            $this->get('dashboard-pengurangan-sampah')->assertOk()->assertViewHas('rekap')->assertViewHas('total')->assertSee('Desa Render');
        }

        // PT dikunci ke PT-nya
        $this->loginAs('pt', ['email' => $pt->npsn]);
        $this->get('rekapsampah?bulan=semua')->assertOk()->assertSee('Desa Render');
        $this->get('dashboard-pengurangan-sampah')->assertOk()->assertSee('Desa Render');
        $ptLain = Satuanpendidikan::factory()->create();
        $this->loginAs('pt', ['email' => $ptLain->npsn]);
        $this->get('rekapsampah?bulan=semua&kodept='.$pt->npsn)->assertOk()->assertDontSee('Desa Render');
        $this->get('dashboard-pengurangan-sampah?kodept='.$pt->npsn)->assertOk()->assertDontSee('Desa Render');

        // Data kosong tetap render
        PendataanPemilahanSampah::query()->delete();
        $this->loginAs('admin');
        $this->get('rekapsampah')->assertOk()->assertSee('Belum ada data Pendataan Sampah Penduduk');
        $this->get('dashboard-pengurangan-sampah')->assertOk();
    }

    public function test_capaiankegiatan_read_only_scope_desa_mahasiswa(): void
    {
        $user = $this->loginAs('mahasiswa');
        $idDesa = Mahasiswa_lokasi::where('id_mahasiswa', $user->mahasiswa->id_mahasiswa)->value('id_desa');
        $desa = Desa::find($idDesa);
        $this->log($user->mahasiswa, ['organik_kg' => 3, 'anorganik_kg' => 1, 'residu_kg' => 1]);
        // Teman sedesa ikut; desa lain tidak
        $this->log($this->mahasiswaDi($desa), ['nama_kepala_keluarga' => 'Siti']);
        $desaLain = Desa::factory()->create(['desa' => 'Desa Tetangga']);
        $this->log($this->mahasiswaDi($desaLain));

        $res = $this->get('capaiankegiatan')->assertOk()
            ->assertSee($desa->desa)->assertDontSee('Desa Tetangga')
            ->assertSee('Persentase Ketaatan Pemilahan [(E/D)*100%]', false)
            ->assertDontSee('capaiankegiatan/tambah', false);
        $this->assertSame(2, $res->viewData('total')->jml_rumah);
        $this->assertEquals(4.0, $res->viewData('total')->organik);

        // Lokasi tanpa kelurahan → pesan, bukan error
        Mahasiswa_lokasi::where('id_mahasiswa', $user->mahasiswa->id_mahasiswa)->update(['id_desa' => null]);
        $this->get('capaiankegiatan')->assertOk()->assertSee('belum terdaftar di lokasi KKN');
    }

    public function test_route_data_sampah_sudah_404_dan_form_capaian_tanpa_sampah(): void
    {
        $ketua = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        Pjdesa::create(['email' => $ketua->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        foreach (['kpisampah', 'kpisampah/listdata', 'kpisampah/listdataserver', 'kpisampah/tambah'] as $uri) {
            $this->get($uri)->assertNotFound();
        }
        foreach (['kpisampah/insert', 'kpisampah/update', 'kpisampah/destroy'] as $uri) {
            $this->put($uri, [])->assertNotFound();
        }
        // Form capaian ketua ada lagi, tapi tanpa input sampah
        $this->get('capaiankegiatan/tambah')->assertOk()->assertDontSee('organik_kg', false)->assertDontSee('data-sampah-form', false);
        $this->get('home')->assertOk()->assertDontSee(url('kpisampah'), false);
    }

    public function test_cache_publik_reset_saat_log_berubah_dan_login_memakai_log(): void
    {
        Cache::flush();
        $kec = Kecamatan::factory()->create(['kecamatan' => 'Kec Publik']);
        $desa = Desa::factory()->create(['id_kecamatan' => $kec->id_kecamatan]);
        $pt = Satuanpendidikan::factory()->create();
        $ketua = $this->mahasiswaDi($desa, $pt->npsn);
        Pjdesa::create(['email' => $ketua->email, 'id_desa' => $desa->id_desa]);

        $this->get('login')->assertOk()->assertDontSee('30,00%');
        $this->log($ketua, ['tanggal' => now()->format('Y-m').'-01', 'organik_kg' => 30, 'anorganik_kg' => 0, 'residu_kg' => 70]);
        $this->get('login')->assertOk()->assertSee('Kec Publik')->assertSee('30,00%');
    }

    public function test_helper_persen_klaster_capaian(): void
    {
        $this->assertNull(PenguranganSampah::persen(5, 0));
        $this->assertNull(PenguranganSampah::klaster(PenguranganSampah::persen(0, 0)));
        $this->assertSame('-', PenguranganSampah::formatPersen(null));
        $this->assertFalse(PenguranganSampah::terpenuhi(19.99));
        $this->assertTrue(PenguranganSampah::terpenuhi(20.0));
        $this->assertEquals(50, PenguranganSampah::capaian(10));
        $this->assertEquals(100, PenguranganSampah::capaian(150.0));
    }
}
