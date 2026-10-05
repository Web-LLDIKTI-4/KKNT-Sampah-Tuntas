<?php

namespace Tests\Feature;

use App\Exports\Sheets\CapaianProgramSheet;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Kpicapaian;
use App\Models\Kpisampah;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Services\KpiSampahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class CapaianPublikTest extends TestCase
{
    use RefreshDatabase;

    private string $bulan;

    private array $d = [];

    /**
     * Lokasi A / Kec Alfa: Desa Satu (Univ Hijau 25%, Univ Tanpa Data tanpa isian) + Desa Tiga (Univ Kuning 15%)
     * -> kecamatan & lokasi = 40/200 = 20,00%. Lokasi B / Kec Beta / Desa Dua: Univ Merah 5%. Total = 45/300 = 15%.
     * kpi_sampah unik per (id_desa, bulan): satu kelurahan hanya punya satu isian per bulan.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->bulan = now()->subMonth()->format('Y-m');

        $this->d['lokasiA'] = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Alfa']);
        $this->d['lokasiB'] = LokasiProgram::factory()->create(['nama_lokasi' => 'Kabupaten Beta']);
        $this->d['kecA'] = Kecamatan::factory()->create(['kecamatan' => 'Kec Alfa']);
        $this->d['kecB'] = Kecamatan::factory()->create(['kecamatan' => 'Kec Beta']);
        $this->d['desaA'] = Desa::factory()->create(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'desa' => 'Desa Satu']);
        $this->d['desaB'] = Desa::factory()->create(['id_kecamatan' => $this->d['kecB']->id_kecamatan, 'desa' => 'Desa Dua']);
        $this->d['desaC'] = Desa::factory()->create(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'desa' => 'Desa Tiga']);

        $this->ketua('hijau', 'Univ Hijau', $this->d['lokasiA'], $this->d['desaA'], 25);
        $this->ketua('kuning', 'Univ Kuning', $this->d['lokasiA'], $this->d['desaC'], 15);
        $this->ketua('merah', 'Univ Merah', $this->d['lokasiB'], $this->d['desaB'], 5);
        $this->ketua('kosong', 'Univ Tanpa Data', $this->d['lokasiA'], $this->d['desaA'], null);
    }

    private function ketua(string $key, string $namaPt, LokasiProgram $lokasi, Desa $desa, ?int $pengurangan): void
    {
        $pt = Satuanpendidikan::factory()->create(['nm_lemb' => $namaPt]);
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'nama' => 'Ketua '.$namaPt, 'location_program' => $lokasi->id]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => $desa->id_desa]);
        $this->d['pt_'.$key] = $pt;
        $this->d['ketua_'.$key] = $mhs;
        if ($pengurangan === null) {
            return;
        }
        Kpisampah::create(KpiSampahService::hitung([
            'email' => $mhs->email, 'id_desa' => $desa->id_desa, 'bulan' => $this->bulan.'-01',
            'jml_rw' => 1, 'jml_penduduk' => 300, 'jml_rumah' => 100, 'jml_rumah_memilah' => 50,
            'timbulan' => 100, 'organik_sumber' => $pengurangan, 'organik_dlh' => 0, 'anorganik_sumber' => 0,
        ]));
    }

    private function publik(array $filter = []): array
    {
        return app(KpiSampahService::class)->drilldownPublik($filter + ['bulan' => null, 'id_kecamatan' => null, 'id_desa' => null, 'klaster' => null]);
    }

    public function test_persen_lokasi_is_cumulative_and_strict_threshold_makes_twenty_yellow(): void
    {
        $data = $this->publik();
        $lokasi = $data['kecamatan']->keyBy('nama_lokasi');

        $this->assertEquals(20.0, $lokasi['Kota Alfa']->persen);
        $this->assertSame('kuning', $lokasi['Kota Alfa']->klaster);
        $this->assertEquals(5.0, $lokasi['Kabupaten Beta']->persen);
        $this->assertSame('merah', $lokasi['Kabupaten Beta']->klaster);
        $this->assertSame('kuning', $lokasi['Kota Alfa']->kecamatan->first()->klaster);
        $this->assertEquals(15.0, $data['total_keseluruhan']->persen_pengurangan);

        $this->assertSame('hijau', Kpisampah::klaster(20.0));
        $this->assertSame('kuning', Kpisampah::klaster(20.0, true));
        $this->assertSame('hijau', Kpisampah::klaster(20.01, true));
        $this->assertSame('kuning', Kpisampah::klaster(10.0, true));
        $this->assertSame('merah', Kpisampah::klaster(9.99, true));
        $this->assertSame('table-success', Kpisampah::warnaSel(20.0));
        $this->assertSame('table-warning', Kpisampah::warnaSel(20.0, true));
    }

    public function test_total_keseluruhan_ignores_kecamatan_and_klaster_filter(): void
    {
        $data = $this->publik(['id_kecamatan' => $this->d['kecB']->id_kecamatan, 'klaster' => 'merah']);

        $this->assertEquals(15.0, $data['total_keseluruhan']->persen_pengurangan);
        // Publik tidak menghitung total per kecamatan (hemat 1 query)
        $this->assertSame($data['total_keseluruhan'], $data['total']);
        // Dashboard tetap: total terfilter kecamatan
        $dashboard = app(KpiSampahService::class)->drilldown(['bulan' => null, 'id_kecamatan' => $this->d['kecB']->id_kecamatan]);
        $this->assertEquals(5.0, $dashboard['total']->persen_pengurangan);
    }

    public function test_capaian_strict_only_reaches_hundred_above_target(): void
    {
        $this->assertEquals(99.99, Kpisampah::capaian(20.0, true));
        $this->assertEquals(100.0, Kpisampah::capaian(20.01, true));
        $this->assertEquals(50.0, Kpisampah::capaian(10.0, true));
        $this->assertNull(Kpisampah::capaian(null, true));
        // Dashboard tetap inklusif
        $this->assertEquals(100.0, Kpisampah::capaian(20.0));
    }

    public function test_persen_pt_is_per_pt_in_kelurahan_and_ketua_has_names_only(): void
    {
        $data = $this->publik(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'id_desa' => $this->d['desaA']->id_desa]);
        $kelompok = $data['kelompok']->keyBy('nama_pt');

        $this->assertEquals(25.0, $kelompok['Univ Hijau']->persen_pt);
        $this->assertSame('hijau', $kelompok['Univ Hijau']->klaster_pt);
        $this->assertNull($kelompok['Univ Tanpa Data']->persen_pt);
        $this->assertNull($kelompok['Univ Tanpa Data']->klaster_pt);
        $this->assertFalse(property_exists($kelompok['Univ Tanpa Data'], 'persen'));
        $this->assertArrayNotHasKey('Univ Kuning', $kelompok->all());
        $this->assertSame(['Ketua Univ Hijau'], $kelompok['Univ Hijau']->ketua->all());
        $this->assertSame(1, $kelompok['Univ Hijau']->jumlah_ketua);
        $this->assertSame(1, $kelompok['Univ Tanpa Data']->jumlah_ketua);
        $this->assertFalse(property_exists($kelompok['Univ Hijau'], 'phone'));

        $res = $this->get('login/laporan?kecamatan='.$this->d['kecA']->id_kecamatan.'&desa='.$this->d['desaA']->id_desa)->assertOk()
            ->assertSee('Pelaksana (PTS) di Kelurahan/Desa Desa Satu')
            ->assertSee('JML. MHS')->assertSee('JML. DPL')
            ->assertSee('Ketua Univ Hijau')->assertSee('ri-eye-line', false)
            ->assertSee('25,00%')
            // Revisi-5/6: no. kontak ketua boleh tampil, email tidak
            ->assertDontSee($this->d['ketua_hijau']->email)->assertDontSee($this->d['ketua_kosong']->email);
        // Badge klaster di sel tabel (diakhiri </td>/</th>), bukan legend di atas tabel
        $this->assertStringContainsString('<span class="badge bg-success">Hijau</span></t', $res->getContent());
    }

    public function test_detail_publik_only_shows_capaian_of_selected_bulan(): void
    {
        // Sampah hanya di $this->bulan (default publik); capaian ketua ada di bulan tsb dan bulan berjalan
        $bulanLain = now()->format('Y-m');
        Kpicapaian::factory()->create(['email' => $this->d['ketua_hijau']->email, 'bulan' => $this->bulan.'-01', 'permasalahan' => 'Masalah Bulan Ini']);
        Kpicapaian::factory()->create(['email' => $this->d['ketua_hijau']->email, 'bulan' => $bulanLain.'-01', 'permasalahan' => 'Masalah Bulan Lain']);
        $filter = ['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'id_desa' => $this->d['desaA']->id_desa];

        $data = $this->publik($filter);
        $this->assertSame($this->bulan, $data['params']['bulan']);
        $kelompok = $data['kelompok']->keyBy('nama_pt');
        $this->assertSame(['Masalah Bulan Ini'], $kelompok['Univ Hijau']->capaian->pluck('permasalahan')->all());
        // Ketua tanpa isian bulan tsb tetap tampil sebagai baris kosong
        $this->assertNull($kelompok['Univ Tanpa Data']->detail->first()->permasalahan);

        $this->get('login/laporan?kecamatan='.$filter['id_kecamatan'].'&desa='.$filter['id_desa'])->assertOk()
            ->assertSee('Masalah Bulan Ini')->assertDontSee('Masalah Bulan Lain');
    }

    public function test_drilldown_publik_with_kodept_only_contains_that_pt(): void
    {
        $kodept = $this->d['pt_hijau']->npsn;
        $data = $this->publik(['kodept' => $kodept, 'id_kecamatan' => $this->d['kecA']->id_kecamatan, 'id_desa' => $this->d['desaA']->id_desa]);

        // Hanya lokasi/kecamatan/kelurahan/PT milik Univ Hijau; persen & total dari data PT tsb (25/100)
        $this->assertSame(['Kota Alfa'], $data['kecamatan']->pluck('nama_lokasi')->all());
        $this->assertEquals(25.0, $data['kecamatan']->first()->persen);
        $this->assertSame(['Kec Alfa'], $data['kecamatan']->first()->kecamatan->pluck('kecamatan')->all());
        $this->assertSame(['Desa Satu'], $data['kelurahan']->pluck('desa')->all());
        $this->assertSame(['Univ Hijau'], $data['kelompok']->pluck('nama_pt')->all());
        $this->assertEquals(25.0, $data['total']->persen_pengurangan);

        // Tanpa kodept (halaman login) tetap semua PT
        $this->assertEquals(15.0, $this->publik()['total']->persen_pengurangan);
    }

    public function test_pt_dashboard_cannot_see_other_pt_via_kodept_or_wilayah_and_login_cache_unaffected(): void
    {
        $hijau = $this->d['pt_hijau']->npsn;
        $url = 'dashboardkpi?kodept='.$hijau.'&kecamatan='.$this->d['kecA']->id_kecamatan.'&desa='.$this->d['desaA']->id_desa;

        // Cache login terisi lebih dulu (tanpa kodept)
        $this->get('login/laporan?kecamatan='.$this->d['kecA']->id_kecamatan.'&desa='.$this->d['desaA']->id_desa)->assertOk()
            ->assertSee('Univ Hijau')->assertSee('Univ Tanpa Data');

        // PT Merah membuka wilayah & kodept milik PT lain: tetap terkunci ke PT Merah
        $this->loginAs('pt', ['email' => $this->d['pt_merah']->npsn]);
        $this->get($url)->assertOk()
            ->assertViewHas('laporan', fn ($l) => $l['kelompok']->isEmpty()
                && $l['kecamatan']->pluck('nama_lokasi')->all() === ['Kabupaten Beta']
                && $l['total']->persen_pengurangan == 5.0)
            ->assertDontSee('Univ Hijau')->assertDontSee('Ketua Univ Hijau')->assertDontSee('Univ Tanpa Data');
        $this->get($url, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertViewIs('laporan._capaian_publik')
            ->assertDontSee('Univ Hijau')->assertDontSee('Kec Alfa')->assertDontSee('Kota Alfa');

        // Admin boleh memilih kodept: hanya PT tsb
        $this->loginAs('admin');
        $this->get($url, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()
            ->assertSee('Univ Hijau')->assertDontSee('Univ Tanpa Data');

        // Halaman login (tamu) tetap semua PT setelah akses dashboard
        $this->app['auth']->guard()->logout();
        $this->get('login/laporan?kecamatan='.$this->d['kecA']->id_kecamatan.'&desa='.$this->d['desaA']->id_desa)->assertOk()
            ->assertSee('Univ Hijau')->assertSee('Univ Tanpa Data');
        $this->get('login/laporan?kodept='.$hijau.'&kecamatan='.$this->d['kecA']->id_kecamatan.'&desa='.$this->d['desaA']->id_desa)->assertOk()
            ->assertSee('Univ Tanpa Data');
    }

    public function test_dashboard_rejects_invalid_klaster(): void
    {
        $this->loginAs('admin');
        $this->get('dashboardkpi?klaster=ungu')->assertSessionHasErrors('klaster');
        $this->get('dashboardkpi?klaster=hijau')->assertSessionDoesntHaveErrors('klaster');
    }

    public function test_export_capaian_program_admin_only_with_structure_and_fill(): void
    {
        $this->loginAs('admin');
        $this->get('dashboardkpi/export-capaian?bulan='.$this->bulan)->assertOk()->assertDownload('capaian-program-'.$this->bulan.'.xlsx');

        $sheet = new CapaianProgramSheet(app(KpiSampahService::class)->capaianProgram(['bulan' => $this->bulan]));
        $rows = $sheet->array();
        // Lokasi (urut nama) -> kecamatan -> kelurahan; 1 baris kosong hanya antar lokasi; persen pecahan, klaster strict
        $this->assertSame(['Nama', 'Persentase Pengurangan Sampah (%)', 'Klaster'], $rows[1]);
        $this->assertSame([
            ['Kabupaten Beta', 0.05, 'Merah'], ['Kec Beta', 0.05, 'Merah'], ['Desa Dua', 0.05, 'Merah'], [null, null, null],
            ['Kota Alfa', 0.2, 'Kuning'], ['Kec Alfa', 0.2, 'Kuning'],
            ['Desa Satu', 0.25, 'Hijau'], ['Desa Tiga', 0.15, 'Kuning'],
        ], array_slice($rows, 2));
        $this->assertSame([3 => 'lokasi', 4 => 'kecamatan', 7 => 'lokasi', 8 => 'kecamatan'], $sheet->jenisBaris());

        // Fill warna di file xlsx asli
        $path = tempnam(sys_get_temp_dir(), 'cap').'.xlsx';
        file_put_contents($path, Excel::raw(new CapaianProgramSheet(app(KpiSampahService::class)->capaianProgram(['bulan' => $this->bulan])), \Maatwebsite\Excel\Excel::XLSX));
        $ws = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
        unlink($path);
        // Lokasi biru gelap + teks putih, kecamatan kuning; kelurahan: hanya sel klaster (C) berwarna klaster
        $this->assertSame('4472C4', $ws->getStyle('A3')->getFill()->getStartColor()->getRGB());
        foreach (['A3', 'B3', 'C3'] as $sel) {
            $this->assertSame('FFFFFF', $ws->getStyle($sel)->getFont()->getColor()->getRGB(), $sel);
        }
        $this->assertSame('FFFF00', $ws->getStyle('A4')->getFill()->getStartColor()->getRGB());
        $this->assertNotSame('FFFFFF', $ws->getStyle('A4')->getFont()->getColor()->getRGB());
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE, $ws->getStyle('A5')->getFill()->getFillType());
        $this->assertSame(Kpisampah::KLASTER['merah']['rgb'], $ws->getStyle('C5')->getFill()->getStartColor()->getRGB());
        $this->assertSame(Kpisampah::KLASTER['hijau']['rgb'], $ws->getStyle('C9')->getFill()->getStartColor()->getRGB());
        $this->assertSame(Kpisampah::KLASTER['kuning']['rgb'], $ws->getStyle('C10')->getFill()->getStartColor()->getRGB());
        // Kelurahan tanpa klaster ("-"): tanpa fill
        $tanpa = new CapaianProgramSheet(['bulan' => $this->bulan, 'klaster' => null, 'kelurahan' => collect(['x|y' => collect([(object) ['desa' => 'Desa Nol', 'persen' => null, 'klaster' => null]])]),
            'lokasi' => collect([(object) ['id_lokasi' => 'x', 'nama_lokasi' => 'Lokasi X', 'persen' => null, 'klaster' => null,
                'kecamatan' => collect([(object) ['id_kecamatan' => 'y', 'kecamatan' => 'Kec Y', 'persen' => null, 'klaster' => null]])]])]);
        file_put_contents($path, Excel::raw($tanpa, \Maatwebsite\Excel\Excel::XLSX));
        $wsTanpa = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
        unlink($path);
        $this->assertSame('-', $wsTanpa->getCell('C5')->getValue());
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE, $wsTanpa->getStyle('C5')->getFill()->getFillType());
        $this->assertSame('0.00%', $ws->getStyle('B5')->getNumberFormat()->getFormatCode());
        // Baris sekat antar lokasi (6): merge A:C, tanpa border
        $this->assertContains('A6:C6', array_values($ws->getMergeCells()));
        foreach (['A6', 'B6', 'C6'] as $sel) {
            $b = $ws->getStyle($sel)->getBorders();
            foreach ([$b->getTop(), $b->getBottom(), $b->getLeft(), $b->getRight()] as $sisi) {
                $this->assertSame(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE, $sisi->getBorderStyle(), $sel);
            }
        }

        // Filter klaster ikut: merah hanya Kabupaten Beta
        $merah = (new CapaianProgramSheet(app(KpiSampahService::class)->capaianProgram(['bulan' => $this->bulan, 'klaster' => 'merah'])))->array();
        $this->assertStringContainsString('Klaster Merah', $merah[0][0]);
        $this->assertSame(['Kabupaten Beta', 'Kec Beta', 'Desa Dua'], array_values(array_filter(array_column(array_slice($merah, 2), 0), fn ($n) => $n && $n !== 'Kelurahan')));
    }

    public function test_export_capaian_program_rejected_for_kepala_and_pt(): void
    {
        // Middleware role proyek menolak dengan redirect ke home (pola sama dengan rekapsampah/export)
        $this->loginAs('kepala');
        $this->get('dashboardkpi/export-capaian')->assertRedirect(route('home'));
        $this->loginAs('pt', ['email' => $this->d['pt_hijau']->npsn]);
        $this->get('dashboardkpi/export-capaian')->assertRedirect(route('home'));
    }

    public function test_export_capaian_program_matches_dashboard_and_handles_empty_data(): void
    {
        // Kelurahan berketua tanpa data sampah di Kec Alfa
        $desaKosong = Desa::factory()->create(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'desa' => 'Desa Kosong']);
        $mhs = Mahasiswa::factory()->create(['location_program' => $this->d['lokasiA']->id, 'kodept' => $this->d['pt_hijau']->npsn]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => $desaKosong->id_desa]);
        // Data bulan lain tidak boleh ikut ke bulan terpilih
        Kpisampah::create(KpiSampahService::hitung([
            'email' => $mhs->email, 'id_desa' => $desaKosong->id_desa, 'bulan' => now()->subMonths(2)->format('Y-m').'-01',
            'jml_rw' => 1, 'jml_penduduk' => 300, 'jml_rumah' => 100, 'jml_rumah_memilah' => 50,
            'timbulan' => 100, 'organik_sumber' => 90, 'organik_dlh' => 0, 'anorganik_sumber' => 0,
        ]));
        // Kelurahan tepat 20% -> Kuning (ambang strict > 20%)
        $this->ketua('pas', 'Univ Pas', $this->d['lokasiA'], Desa::factory()->create(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'desa' => 'Desa Pas']), 20);

        $this->loginAs('admin');
        $rows = (new CapaianProgramSheet(app(KpiSampahService::class)->capaianProgram(['bulan' => $this->bulan])))->array();
        $baris = collect(array_slice($rows, 2))->filter(fn ($r) => $r[0] && $r[0] !== 'Kelurahan')->keyBy(0);

        // Angka layar dashboard (bulan sama): lokasi, kecamatan, kelurahan per kecamatan
        $persenKe = fn ($p) => $p === null ? '-' : round($p / 100, 4);
        $klasterKe = fn ($k) => $k ? Kpisampah::KLASTER[$k]['label'] : '-';
        $layar = $this->get('dashboardkpi?bulan='.$this->bulan)->assertOk()->viewData('laporan');
        foreach ($layar['kecamatan'] as $lokasi) {
            $this->assertEquals([$persenKe($lokasi->persen), $klasterKe($lokasi->klaster)], array_slice($baris[$lokasi->nama_lokasi], 1));
            foreach ($lokasi->kecamatan as $kec) {
                $this->assertEquals([$persenKe($kec->persen), $klasterKe($kec->klaster)], array_slice($baris[$kec->kecamatan], 1));
                $kel = $this->get('dashboardkpi?bulan='.$this->bulan.'&kecamatan='.$kec->id_kecamatan)->assertOk()->viewData('laporan')['kelurahan'];
                $this->assertNotEmpty($kel);
                foreach ($kel as $k) {
                    $this->assertEquals([$persenKe($k->persen), $klasterKe($k->klaster)], array_slice($baris[$k->desa], 1), $k->desa);
                }
            }
        }
        $this->assertSame(['Desa Kosong', '-', '-'], $baris['Desa Kosong']);
        $this->assertSame(['Desa Pas', 0.2, 'Kuning'], $baris['Desa Pas']);

        // Kecamatan tanpa kelurahan: tidak error, hanya baris lokasi + kecamatan
        $kosong = (new CapaianProgramSheet(['bulan' => $this->bulan, 'klaster' => null, 'kelurahan' => collect(), 'lokasi' => collect([(object) [
            'id_lokasi' => 'x', 'nama_lokasi' => 'Lokasi X', 'persen' => null, 'klaster' => null,
            'kecamatan' => collect([(object) ['id_kecamatan' => 'y', 'kecamatan' => 'Kec Y', 'persen' => null, 'klaster' => null]]),
        ]])]))->array();
        $this->assertSame([['Lokasi X', '-', '-'], ['Kec Y', '-', '-']], array_slice($kosong, 2));
    }

    public function test_export_capaian_button_and_download_only_for_admin(): void
    {
        $this->get('dashboardkpi/export-capaian')->assertRedirect(route('login'));

        foreach ([['kepala', []], ['pt', ['email' => $this->d['pt_hijau']->npsn]]] as [$role, $attr]) {
            $this->loginAs($role, $attr);
            $this->get('dashboardkpi')->assertOk()->assertDontSee('export-capaian', false);
            $this->get('dashboardkpi', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertDontSee('export-capaian', false);
        }

        $this->loginAs('admin');
        $this->get('dashboardkpi')->assertOk()->assertSee('dashboardkpi/export-capaian', false);
    }

    public function test_guest_can_download_capaian_program_with_validation_cache_and_throttle(): void
    {
        $url = 'login/laporan/export?bulan='.$this->bulan;
        $this->get($url)->assertOk()->assertDownload('capaian-program-'.$this->bulan.'.xlsx');
        $this->getJson('login/laporan/export?bulan=9999-12')->assertUnprocessable()->assertJsonValidationErrors('bulan');
        $this->getJson('login/laporan/export?klaster=ungu')->assertUnprocessable()->assertJsonValidationErrors('klaster');

        // Hanya agregat: tanpa email/no. HP/nama ketua
        $data = app(KpiSampahService::class)->capaianProgram(['bulan' => $this->bulan]);
        foreach (['hijau', 'kuning', 'merah', 'kosong'] as $k) {
            $this->assertStringNotContainsString($this->d['ketua_'.$k]->email, serialize($data));
            $this->assertStringNotContainsString($this->d['ketua_'.$k]->nama, serialize($data));
            $this->assertStringNotContainsString((string) $this->d['ketua_'.$k]->phone, serialize($data));
        }

        // Unduhan berulang memakai cache: tanpa query DB
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();

        // Throttle 10/menit (2 request sukses + 2 ditolak validasi di atas sudah terhitung)
        for ($i = 0; $i < 6; $i++) {
            $this->get($url)->assertOk();
        }
        $this->get($url)->assertStatus(429);
        // Route admin tetap ada
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('dashboardkpi.export-capaian'));
    }

    public function test_klaster_filters_rows_without_recalculating(): void
    {
        $merah = $this->publik(['klaster' => 'merah']);
        $this->assertSame(['Kabupaten Beta'], $merah['kecamatan']->pluck('nama_lokasi')->all());
        $this->assertSame('merah', $merah['params']['klaster']);
        $this->assertEquals(15.0, $merah['total_keseluruhan']->persen_pengurangan);

        $hijau = $this->publik(['klaster' => 'hijau']);
        $this->assertTrue($hijau['kecamatan']->isEmpty());

        $pt = $this->publik(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'id_desa' => $this->d['desaA']->id_desa, 'klaster' => 'hijau']);
        $this->assertSame(['Univ Hijau'], $pt['kelompok']->pluck('nama_pt')->all());
        $this->assertEquals(25.0, $pt['kelompok']->first()->persen_pt);

        $this->get('login/laporan?klaster=merah')->assertOk()->assertSee('Kec Beta')->assertDontSee('Kec Alfa');
        $this->get('login/laporan?klaster=hijau')->assertOk()->assertSee('Tidak ada kecamatan pada klaster ini');
    }

    public function test_jumlah_ketua_counts_ketua_per_pt_in_kelurahan_and_matches_dashboard(): void
    {
        $pt = $this->d['pt_hijau'];
        foreach ([['Ketua Dua', $this->d['desaA']], ['Ketua Lain Desa', $this->d['desaC']]] as [$nama, $desa]) {
            $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'nama' => $nama, 'location_program' => $this->d['lokasiA']->id]);
            Pjdesa::create(['email' => $mhs->email, 'id_desa' => $desa->id_desa]);
        }
        // Mahasiswa biasa (bukan ketua) tidak dihitung
        Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $this->d['lokasiA']->id]);

        $filter = ['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'id_desa' => $this->d['desaA']->id_desa];
        $publik = $this->publik($filter)['kelompok']->keyBy('nama_pt');
        $dashboard = app(KpiSampahService::class)->drilldown($filter + ['bulan' => null])['kelompok']->keyBy('nama_pt');

        $this->assertSame(2, $publik['Univ Hijau']->jumlah_ketua);
        $this->assertSame(['Ketua Dua', 'Ketua Univ Hijau'], $publik['Univ Hijau']->ketua->all());
        $this->assertSame($dashboard['Univ Hijau']->jumlah_ketua, $publik['Univ Hijau']->jumlah_ketua);
        // Tiap ketua punya baris detail walau belum mengisi capaian
        $this->assertSame(['Ketua Dua', 'Ketua Univ Hijau'], $publik['Univ Hijau']->detail->pluck('nama_ketua')->all());
    }

    public function test_bulan_outside_available_list_is_rejected(): void
    {
        $this->getJson('login/laporan?bulan='.$this->bulan)->assertOk();
        $this->getJson('login/laporan?bulan=1999-01')->assertUnprocessable()->assertJsonValidationErrors('bulan');
        $this->getJson('login/laporan?bulan=9999-12')->assertUnprocessable()->assertJsonValidationErrors('bulan');
    }

    public function test_cached_data_and_html_contain_no_email(): void
    {
        $email = $this->d['ketua_hijau']->email;
        Kpicapaian::factory()->create(['email' => $email, 'permasalahan' => 'Masalah Uji', 'bulan' => $this->bulan.'-01']);
        Cache::flush();

        $this->get('login/laporan?kecamatan='.$this->d['kecA']->id_kecamatan.'&desa='.$this->d['desaA']->id_desa)->assertOk()
            ->assertSee('Masalah Uji')->assertDontSee($email);

        $data = $this->publik(['id_kecamatan' => $this->d['kecA']->id_kecamatan, 'id_desa' => $this->d['desaA']->id_desa]);
        $this->assertStringNotContainsString($email, serialize($data));

        // Revisi-5: no. kontak pengisi capaian boleh tampil, email tidak
        $isian = $data['kelompok']->firstWhere('nama_pt', 'Univ Hijau')->capaian->first();
        $this->assertSame($this->d['ketua_hijau']->phone, $isian->phone);
        $this->assertFalse(property_exists($isian, 'email'));

        // Revisi-6: detail = baris capaian + ketua yang belum mengisi, urut nama ketua
        $hijau = $data['kelompok']->firstWhere('nama_pt', 'Univ Hijau');
        $this->assertSame(['Ketua Univ Hijau'], $hijau->detail->pluck('nama_ketua')->all());
        $this->assertSame('Masalah Uji', $hijau->detail->first()->permasalahan);
        $this->assertSame($this->d['ketua_hijau']->phone, $hijau->detail->first()->phone);

        $kosong = $data['kelompok']->firstWhere('nama_pt', 'Univ Tanpa Data')->detail;
        $this->assertCount(1, $kosong);
        $this->assertSame('Ketua Univ Tanpa Data', $kosong->first()->nama_ketua);
        $this->assertSame($this->d['ketua_kosong']->phone, $kosong->first()->phone);
        $this->assertNull($kosong->first()->permasalahan);
        $this->assertNull($kosong->first()->status_capaian);
        $this->assertFalse(property_exists($kosong->first(), 'email'));
        $this->assertFalse(property_exists($hijau, 'ketua_email'));
    }

    public function test_invalid_klaster_is_rejected(): void
    {
        $this->getJson('login/laporan?klaster=ungu')->assertUnprocessable()->assertJsonValidationErrors('klaster');
        $this->getJson('login/laporan?klaster[]=hijau')->assertUnprocessable()->assertJsonValidationErrors('klaster');
        $this->getJson('login/laporan?klaster=')->assertOk();
    }

    public function test_action_button_only_on_default_and_reset_otherwise(): void
    {
        // Guest: Export Excel (bila route publik sudah ada) menggantikan PNG
        $aksi = Route::has('login.laporan.export') ? route('login.laporan.export') : 'data-png-download';
        $this->get('login')->assertOk()
            ->assertSee($aksi, false)->assertDontSee('data-drill-reset', false)
            ->assertSee('Program GRADASI : KKN Tematik')->assertSee('Capaian Program')->assertSee('Dokumen');
        $this->get('login/laporan?bulan='.$this->bulan)->assertOk()->assertSee($aksi, false);
        $this->get('login/laporan?klaster=kuning')->assertOk()->assertSee('data-drill-reset', false)->assertDontSee($aksi, false);
        $this->get('login/laporan?kecamatan='.$this->d['kecA']->id_kecamatan)->assertOk()->assertSee('data-drill-reset', false);
    }

    public function test_dashboard_uses_public_partial_and_strict_threshold(): void
    {
        // Kec Alfa tepat 20,00%: kuning di publik & dashboard (> 20%)
        $this->get('login/laporan')->assertOk()
            ->assertSee('<span class="badge bg-warning">Kuning</span></t', false)
            ->assertDontSee('<span class="badge bg-success">Hijau</span></t', false);
        $this->loginAs('kepala');
        $this->get('dashboardkpi', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertViewIs('laporan._capaian_publik')
            ->assertSee('<span class="badge bg-warning">Kuning</span></t', false)
            ->assertDontSee('<span class="badge bg-success">Hijau</span></t', false)
            ->assertSee('data-png-download', false)->assertSee('name="klaster"', false);
    }

    public function test_admin_sees_export_instead_of_png_in_laporan_kegiatan(): void
    {
        if (! Route::has('dashboardkpi.export-capaian')) {
            $this->markTestSkipped('Route dashboardkpi.export-capaian belum ada');
        }

        $this->loginAs('admin');
        $this->get('dashboardkpi', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()
            ->assertSee('dashboardkpi/export-capaian', false)->assertDontSee('data-png-download', false);
        // Guest (login) tidak memakai export dashboard
        auth()->logout();
        $this->get('login/laporan')->assertOk()->assertDontSee('export-capaian', false);
    }
}
