<?php

namespace Tests\Feature;

use App\Exports\LogBulananLengkapExport;
use App\Exports\LogHarianLengkapExport;
use App\Exports\LogKehadiranByMhsExport;
use App\Exports\LogKehadiranLengkapExport;
use App\Models\Logkegiatan;
use App\Models\Dplmentoring;
use App\Models\Kehadiran;
use App\Models\Logbulanan;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Maatwebsite\Excel\Cache\BatchCache;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Settings;
use Tests\TestCase;

class LogKeseluruhanExportTest extends TestCase
{
    use RefreshDatabase;

    private const SEMUA = ['mhsA1@uji.test', 'mhsA2@uji.test', 'mhsB1@uji.test'];

    private const JENIS = [
        'logbulanan' => ['prefix' => 'log_bulanan', 'class' => LogBulananLengkapExport::class, 'email' => 'lb.email'],
        'logkehadiran' => ['prefix' => 'log_kehadiran', 'class' => LogKehadiranLengkapExport::class, 'email' => 'k.email'],
    ];

    private array $d = [];

    // ptA: mhsA1 (bimbingan dplA), mhsA2; ptB: mhsB1. Tiap mahasiswa punya data Sep 2026 & Agu 2026
    protected function setUp(): void
    {
        parent::setUp();
        $this->d['ptA'] = Satuanpendidikan::factory()->create();
        $this->d['ptB'] = Satuanpendidikan::factory()->create();
        foreach (['mhsA1' => 'ptA', 'mhsA2' => 'ptA', 'mhsB1' => 'ptB'] as $key => $pt) {
            $mhs = Mahasiswa::factory()->create(['kodept' => $this->d[$pt]->npsn, 'email' => $key.'@uji.test']);
            Logbulanan::factory()->create(['email' => $mhs->email, 'tahun' => 2026, 'bulan' => 9]);
            Logbulanan::factory()->create(['email' => $mhs->email, 'tahun' => 2026, 'bulan' => 8]);
            Kehadiran::factory()->create(['email' => $mhs->email, 'tanggal' => '2026-09-05']);
            Kehadiran::factory()->create(['email' => $mhs->email, 'tanggal' => '2026-08-05']);
            $this->d[$key] = $mhs;
        }
        Dplmentoring::create(['email_mahasiswa' => 'mhsA1@uji.test', 'email_dpl' => 'dplA@uji.test']);
    }

    private function user(string $role, string $email): User
    {
        return User::factory()->role($role)->create(['email' => $email]);
    }

    // Email unik & jumlah baris yang ikut di export lewat HTTP
    private function export(string $jenis, User $user, string $query = ''): array
    {
        $cfg = self::JENIS[$jenis];
        Excel::fake();
        Excel::matchByRegex();
        $this->actingAs($user)->get('export/'.$jenis.$query)->assertOk();

        $hasil = [];
        Excel::assertDownloaded('/^'.$cfg['prefix'].'_.+\.xlsx$/', function ($export) use ($cfg, &$hasil) {
            $this->assertInstanceOf($cfg['class'], $export);
            $hasil = [
                $export->query()->pluck($cfg['email'])->unique()->sort()->values()->all(),
                $export->query()->count(),
            ];

            return true;
        });

        return $hasil;
    }

    public function test_admin_kepala_pemda_export_semua_data(): void
    {
        // Jatah throttle dipakai bersama antar route export; throttle diuji terpisah
        $this->withoutMiddleware(ThrottleRequests::class);
        foreach (array_keys(self::JENIS) as $jenis) {
            foreach (['admin', 'kepala', 'pemda'] as $role) {
                $this->assertSame([self::SEMUA, 6], $this->export($jenis, $this->user($role, "$role.$jenis@uji.test")), "$jenis $role");
            }
        }
    }

    public function test_pt_hanya_mahasiswa_pt_nya_dan_idor_diabaikan(): void
    {
        // Jatah throttle dipakai bersama antar route export; throttle diuji terpisah
        $this->withoutMiddleware(ThrottleRequests::class);
        $pt = $this->user('pt', $this->d['ptA']->npsn);
        $idor = '?kodept='.$this->d['ptB']->npsn.'&email=mhsB1@uji.test&id_mahasiswa='.$this->d['mhsB1']->id_mahasiswa;

        foreach (array_keys(self::JENIS) as $jenis) {
            $this->assertSame([['mhsA1@uji.test', 'mhsA2@uji.test'], 4], $this->export($jenis, $pt), $jenis);
            $this->assertSame([['mhsA1@uji.test', 'mhsA2@uji.test'], 4], $this->export($jenis, $pt, $idor), $jenis);
        }
    }

    public function test_dpl_hanya_mahasiswa_bimbingan_dan_idor_diabaikan(): void
    {
        // Jatah throttle dipakai bersama antar route export; throttle diuji terpisah
        $this->withoutMiddleware(ThrottleRequests::class);
        $dpl = $this->user('dpl', 'dplA@uji.test');
        $dplTanpa = $this->user('dpl', 'dplTanpa@uji.test');

        foreach (array_keys(self::JENIS) as $jenis) {
            $this->assertSame([['mhsA1@uji.test'], 2], $this->export($jenis, $dpl), $jenis);
            $this->assertSame([['mhsA1@uji.test'], 2], $this->export($jenis, $dpl, '?email=mhsB1@uji.test&email_dpl=lain@uji.test'), $jenis);
            $this->assertSame([[], 0], $this->export($jenis, $dplTanpa), $jenis);
        }
    }

    public function test_mahasiswa_dan_guest_ditolak(): void
    {
        Excel::fake();
        foreach (array_keys(self::JENIS) as $jenis) {
            $this->get('export/'.$jenis)->assertRedirect(route('login'));
        }
        foreach (array_keys(self::JENIS) as $jenis) {
            $res = $this->actingAs($this->user('mahasiswa', "x.$jenis@uji.test"))->get('export/'.$jenis);
            $this->assertTrue($res->isRedirect(), "$jenis mahasiswa harus ditolak");
            $this->assertNotSame(200, $res->getStatusCode());
        }

        // Role tak dikenal di class export → kosong
        $lain = new User(['email' => 'lain@uji.test']);
        $lain->role = 'tamu';
        $this->assertSame(0, (new LogBulananLengkapExport($lain))->query()->count());
        $this->assertSame(0, (new LogKehadiranLengkapExport($lain))->query()->count());
    }

    public function test_filter_bulan_opsional_dan_validasi(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $admin = $this->user('admin', 'admin@uji.test');
        $pt = $this->user('pt', $this->d['ptA']->npsn);

        foreach (array_keys(self::JENIS) as $jenis) {
            $prefix = self::JENIS[$jenis]['prefix'];
            $this->assertSame([self::SEMUA, 3], $this->export($jenis, $admin, '?bulan=2026-08'), $jenis);
            Excel::assertDownloaded('/^'.$prefix.'_2026-08_.+\.xlsx$/');
            $this->assertSame([['mhsA1@uji.test', 'mhsA2@uji.test'], 2], $this->export($jenis, $pt, '?bulan=2026-09'), $jenis);
            // Kosong = semua data
            $this->assertSame([self::SEMUA, 6], $this->export($jenis, $admin, '?bulan='), $jenis);
            Excel::assertDownloaded('/^'.$prefix.'_semua_.+\.xlsx$/');

            foreach (['2026-13', 'abc', "2026-08' OR 1=1"] as $bulan) {
                $this->actingAs($admin)->get('export/'.$jenis.'?bulan='.urlencode($bulan))->assertSessionHasErrors('bulan');
            }
        }
    }

    public function test_throttle_3_per_menit(): void
    {
        Excel::fake();
        foreach (array_keys(self::JENIS) as $jenis) {
            $admin = $this->user('admin', "admin.$jenis@uji.test");
            for ($i = 0; $i < 3; $i++) {
                $this->actingAs($admin)->get('export/'.$jenis)->assertOk();
            }
            $this->actingAs($admin)->get('export/'.$jenis)->assertStatus(429);
        }
    }

    public function test_export_per_mahasiswa_di_luar_scope_404(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Excel::fake();
        $pt = $this->user('pt', $this->d['ptA']->npsn);
        $dpl = $this->user('dpl', 'dplA@uji.test');

        foreach (['admlogbulanan', 'admlogkehadiran'] as $uri) {
            $this->actingAs($pt)->get("$uri/export/mhsA2@uji.test")->assertOk();
            $this->actingAs($pt)->get("$uri/export/mhsB1@uji.test")->assertNotFound();
            $this->actingAs($dpl)->get("$uri/export/mhsA1@uji.test")->assertOk();
            $this->actingAs($dpl)->get("$uri/export/mhsA2@uji.test")->assertNotFound();
        }
    }

    public function test_throttle_export_per_mahasiswa(): void
    {
        Excel::fake();
        $routes = [
            'admlogkegiatan/export/mhsA1@uji.test',
            'admlogbulanan/export/mhsA1@uji.test',
            'admlogkehadiran/export/mhsA1@uji.test',
            'admlaporandpl/export/dplA@uji.test',
        ];

        // User baru per route: jatah throttle dipakai bersama antar route
        foreach ($routes as $i => $uri) {
            $admin = $this->user('admin', "admin$i@uji.test");
            for ($n = 0; $n < 3; $n++) {
                $this->actingAs($admin)->get($uri)->assertOk();
            }
            $this->actingAs($admin)->get($uri)->assertStatus(429);
        }

        // Jatah dibagi dengan export keseluruhan
        $admin = $this->user('admin', 'adminCampur@uji.test');
        $this->actingAs($admin)->get('export/logbulanan')->assertOk();
        $this->actingAs($admin)->get('logharian/export')->assertOk();
        $this->actingAs($admin)->get('admlogkehadiran/export/mhsA1@uji.test')->assertOk();
        $this->actingAs($admin)->get('admlogbulanan/export/mhsA1@uji.test')->assertStatus(429);
    }

    public function test_log_kehadiran_per_mahasiswa_anti_formula(): void
    {
        $mhs = $this->d['mhsA1'];
        $mhs->update(['nama' => '=HYPERLINK("http://x","klik")', 'nim' => '+62123']);
        Kehadiran::where('email', $mhs->email)->update(['status_kehadiran' => '=1+1']);

        $ws = $this->sheet(new LogKehadiranByMhsExport($mhs->email));

        $this->assertSame(DataType::TYPE_STRING, $ws->getCell('C2')->getDataType());
        $this->assertSame('=HYPERLINK("http://x","klik")', $ws->getCell('C2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $ws->getCell('F2')->getDataType());
        $this->assertSame('=1+1', $ws->getCell('F2')->getValue());
        // Hanya data mahasiswa tsb
        $this->assertSame(3, $ws->getHighestRow());
    }

    public function test_export_jalan_dengan_cache_driver_batch(): void
    {
        $this->assertSame('batch', config('excel.cache.driver'));
        // Limit kecil agar sel benar-benar di-flush ke cache store saat test
        config(['excel.cache.batch.memory_limit' => 5]);
        Logkegiatan::factory()->count(3)->create(['email' => 'mhsA1@uji.test', 'tanggal' => '2026-09-05']);
        $admin = $this->user('admin', 'admin@uji.test');

        $ws = $this->sheet(new LogHarianLengkapExport($admin));
        $this->assertInstanceOf(BatchCache::class, Settings::getCache());
        $this->assertSame('Timestamp', $ws->getCell('A1')->getValue());
        $this->assertSame(4, $ws->getHighestRow());
        $this->assertSame('mhsA1@uji.test', $ws->getCell('B4')->getValue());
        $this->assertSame('FFFF00', $ws->getStyle('M1')->getFill()->getStartColor()->getRGB());

        $ws = $this->sheet(new LogBulananLengkapExport($admin));
        $this->assertSame(7, $ws->getHighestRow());
        $this->assertSame(self::SEMUA, collect($ws->rangeToArray('B2:B7'))->flatten()->unique()->sort()->values()->all());

        $ws = $this->sheet(new LogKehadiranLengkapExport($admin));
        $this->assertSame(7, $ws->getHighestRow());
        $this->assertSame('Hadir', $ws->getCell('G7')->getValue());

        $ws = $this->sheet(new LogKehadiranByMhsExport('mhsB1@uji.test'));
        $this->assertSame(3, $ws->getHighestRow());

        // Lewat HTTP (download asli, bukan fake)
        $res = $this->actingAs($this->user('admin', 'admin2@uji.test'))->get('export/logkehadiran');
        $res->assertOk();
        $this->assertStringContainsString('spreadsheetml', $res->headers->get('content-type'));
    }

    private function sheet(object $export)
    {
        $path = tempnam(sys_get_temp_dir(), 'log').'.xlsx';
        file_put_contents($path, Excel::raw($export, ExcelType::XLSX));
        $ws = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        return $ws;
    }

    private function assertHeaderKuning($ws, array $header, string $kolomAkhir): void
    {
        $this->assertSame($header, $ws->rangeToArray('A1:'.$kolomAkhir.'1')[0]);
        $this->assertSame('FFFF00', $ws->getStyle('A1')->getFill()->getStartColor()->getRGB());
        $this->assertSame('FFFF00', $ws->getStyle($kolomAkhir.'1')->getFill()->getStartColor()->getRGB());
        $this->assertTrue($ws->getStyle('A1')->getFont()->getBold());
        $this->assertNotSame('none', $ws->getStyle($kolomAkhir.'2')->getBorders()->getBottom()->getBorderStyle());
        $this->assertFalse($ws->getColumnDimension('B')->getAutoSize());
    }

    public function test_log_bulanan_header_kuning_isi_dan_anti_formula(): void
    {
        $mhs = $this->d['mhsA1'];
        Logbulanan::where('email', $mhs->email)->where('bulan', 9)->update(['deskripsi' => '<p>Kegiatan <b>bank sampah</b></p>', 'nilai' => 90]);
        Logbulanan::where('email', $mhs->email)->where('bulan', 8)->update(['deskripsi' => '=HYPERLINK("http://x","klik")']);

        $ws = $this->sheet(new LogBulananLengkapExport($this->user('dpl', 'dplA@uji.test')));
        $this->assertHeaderKuning($ws, ['Timestamp', 'Email Address', 'Nama Mahasiswa', 'Nomor Kontak', 'NIM', 'Nama Perguruan Tinggi', 'Bulan', 'Deskripsi', 'Nilai'], 'I');

        // Urut tahun, bulan: baris 2 = Agustus, baris 3 = September
        $this->assertSame('mhsA1@uji.test', $ws->getCell('B3')->getValue());
        $this->assertSame($mhs->nama, $ws->getCell('C3')->getValue());
        $this->assertSame($mhs->phone, $ws->getCell('D3')->getValue());
        $this->assertSame($this->d['ptA']->nm_lemb, $ws->getCell('F3')->getValue());
        $this->assertSame('September 2026', $ws->getCell('G3')->getValue());
        $this->assertStringNotContainsString('<', (string) $ws->getCell('H3')->getValue());
        $this->assertStringContainsString('bank sampah', (string) $ws->getCell('H3')->getValue());
        $this->assertEquals(90, $ws->getCell('I3')->getValue());
        $this->assertSame('Agustus 2026', $ws->getCell('G2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $ws->getCell('H2')->getDataType());
        $this->assertStringStartsWith('=HYPERLINK', $ws->getCell('H2')->getValue());
    }

    public function test_log_kehadiran_header_kuning_isi_dan_anti_formula(): void
    {
        $mhs = $this->d['mhsA1'];
        Kehadiran::where('email', $mhs->email)->where('tanggal', '2026-09-05')->update(['waktu_masuk' => '2026-09-05 07:30:00']);
        $mhs->update(['nama' => '=1+1']);

        $ws = $this->sheet(new LogKehadiranLengkapExport($this->user('dpl', 'dplA@uji.test')));
        $this->assertHeaderKuning($ws, ['Email Address', 'Nama Mahasiswa', 'Nomor Kontak', 'NIM', 'Nama Perguruan Tinggi', 'Tanggal', 'Status Kehadiran', 'Jam Masuk', 'Jam Pulang'], 'I');

        // Urut tanggal: baris 2 = 05-08-2026, baris 3 = 05-09-2026
        $this->assertSame('mhsA1@uji.test', $ws->getCell('A3')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $ws->getCell('B3')->getDataType());
        $this->assertSame('=1+1', $ws->getCell('B3')->getValue());
        $this->assertSame($mhs->phone, $ws->getCell('C3')->getValue());
        $this->assertSame('05-09-2026', $ws->getCell('F3')->getValue());
        $this->assertSame('Hadir', $ws->getCell('G3')->getValue());
        $this->assertSame('07:30:00', $ws->getCell('H3')->getValue());
        $this->assertSame('-', $ws->getCell('I3')->getValue());
        $this->assertSame('05-08-2026', $ws->getCell('F2')->getValue());
    }

    public function test_export_kehadiran_per_mahasiswa_jam_kosong_jadi_strip(): void
    {
        Kehadiran::where('email', 'mhsB1@uji.test')->update(['waktu_masuk' => '2026-09-05 07:30:00', 'waktu_pulang' => null]);

        $ws = $this->sheet(new LogKehadiranByMhsExport('mhsB1@uji.test'));

        foreach ([2, 3] as $baris) {
            $this->assertSame('07:30:00', $ws->getCell("G$baris")->getValue());
            $this->assertSame('-', $ws->getCell("H$baris")->getValue());
        }
    }
}
