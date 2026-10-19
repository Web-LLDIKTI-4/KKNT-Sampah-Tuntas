<?php

namespace Tests\Feature;

use App\Exports\LogBulananLengkapExport;
use App\Exports\LogHarianLengkapExport;
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
use Tests\Concerns\ReadsXlsx;
use Tests\TestCase;

class LogKeseluruhanExportTest extends TestCase
{
    use ReadsXlsx;
    use RefreshDatabase;

    private const SEMUA = ['mhsA1@uji.test', 'mhsA2@uji.test', 'mhsB1@uji.test'];

    private const JENIS = [
        // kolom: index kolom Email di file (No. di A)
        'logbulanan' => ['prefix' => 'log_bulanan', 'kolom' => 2],
        'logkehadiran' => ['prefix' => 'log_kehadiran', 'kolom' => 1],
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

    // Email unik & jumlah baris data di file export lewat HTTP
    private function export(string $jenis, User $user, string $query = '', string $periode = '.+'): array
    {
        $cfg = self::JENIS[$jenis];
        $ws = $this->xlsxFromResponse(
            $this->actingAs($user)->get('export/'.$jenis.$query),
            '/filename='.$cfg['prefix'].'_'.$periode.'\.xlsx/'
        );
        $email = collect($this->dataColumn($ws, $cfg['kolom']));

        return [$email->unique()->sort()->values()->all(), $email->count()];
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
            $this->assertSame([self::SEMUA, 3], $this->export($jenis, $admin, '?bulan=2026-08', '2026-08_.+'), $jenis);
            $this->assertSame([['mhsA1@uji.test', 'mhsA2@uji.test'], 2], $this->export($jenis, $pt, '?bulan=2026-09'), $jenis);
            // Kosong = semua data
            $this->assertSame([self::SEMUA, 6], $this->export($jenis, $admin, '?bulan=', 'semua_.+'), $jenis);

            foreach (['2026-13', 'abc', "2026-08' OR 1=1"] as $bulan) {
                $this->actingAs($admin)->get('export/'.$jenis.'?bulan='.urlencode($bulan))->assertSessionHasErrors('bulan');
            }
        }
    }

    public function test_throttle_3_per_menit(): void
    {
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

        $ws = $this->perMahasiswa('admlogkehadiran', $mhs->email);

        $this->assertSame(['No.', 'NIM', 'Nama Mahasiswa', 'Nama Perguruan Tinggi', 'Tanggal', 'Status Kehadiran', 'Jam Masuk', 'Jam Pulang'], $this->headerRow($ws));
        $this->assertCellIsText($ws, 'B4', '+62123');
        $this->assertCellIsText($ws, 'C4', '=HYPERLINK("http://x","klik")');
        $this->assertCellIsText($ws, 'F4', '=1+1');
        $this->assertSame($this->d['ptA']->nm_lemb, $this->cellText($ws, 'D4'));
        $this->assertSame(['05-08-2026', '05-09-2026'], $this->dataColumn($ws, 4));
        // Hanya data mahasiswa tsb
        $this->assertCount(2, $this->dataRows($ws));
    }

    public function test_log_bulanan_per_mahasiswa_isi_dan_null_safe(): void
    {
        $mhs = $this->d['mhsA1'];
        Logbulanan::where('email', $mhs->email)->where('bulan', 9)->update(['deskripsi' => '<p>Kegiatan <b>bank sampah</b></p>', 'nilai' => 90]);
        Logbulanan::where('email', $mhs->email)->where('bulan', 8)->update(['deskripsi' => '=HYPERLINK("http://x","klik")']);

        $ws = $this->perMahasiswa('admlogbulanan', $mhs->email);
        $this->assertSame(['No.', 'NIM', 'Nama Mahasiswa', 'Nama Perguruan Tinggi', 'Bulan', 'Deskripsi', 'Nilai'], $this->headerRow($ws));
        $this->assertCount(2, $this->dataRows($ws));
        $this->assertCellIsText($ws, 'F4', '=HYPERLINK("http://x","klik")');
        $this->assertStringNotContainsString('<', $this->cellText($ws, 'F5'));
        $this->assertStringContainsString('bank sampah', $this->cellText($ws, 'F5'));
        $this->assertEquals(90, $ws->getCell('G5')->getValue());

        // PT tidak ada di master → tetap 200, kolom PT "-"
        $this->d['ptA']->delete();
        foreach (['admlogbulanan', 'admlogkehadiran'] as $uri) {
            $ws = $this->perMahasiswa($uri, $mhs->email);
            $this->assertSame('-', $this->cellText($ws, 'D4'), $uri);
            $this->assertSame($mhs->nama, $this->cellText($ws, 'C4'), $uri);
        }
        // Mahasiswa tidak ada di master → 404 (authorizeStudent), bukan 500
        Logbulanan::factory()->create(['email' => 'yatim@uji.test', 'tahun' => 2026, 'bulan' => 9]);
        $this->actingAs(User::where('email', 'qa.admin@uji.test')->first())->get('admlogbulanan/export/yatim@uji.test')->assertNotFound();
    }

    public function test_export_lengkap_write_to_dan_http(): void
    {
        Logkegiatan::factory()->count(3)->create(['email' => 'mhsA1@uji.test', 'tanggal' => '2026-09-05']);
        $admin = $this->user('admin', 'admin@uji.test');

        $ws = $this->xlsxFromWriter(fn ($path) => (new LogHarianLengkapExport($admin))->writeTo($path));
        $this->assertSame('Log Aktivitas Harian Mahasiswa', $this->cellText($ws, 'A1'));
        $this->assertSame(['No.', 'Timestamp', 'Email Address'], array_slice($this->headerRow($ws), 0, 3));
        $this->assertCount(3, $this->dataRows($ws));
        $this->assertSame('mhsA1@uji.test', $this->cellText($ws, 'C6'));
        $this->assertSame('D9EAD3', $ws->getStyle('N3')->getFill()->getStartColor()->getRGB());

        $ws = $this->xlsxFromWriter(fn ($path) => (new LogBulananLengkapExport($admin))->writeTo($path));
        $this->assertCount(6, $this->dataRows($ws));
        $this->assertSame(self::SEMUA, collect($this->dataColumn($ws, 2))->unique()->sort()->values()->all());

        $ws = $this->xlsxFromWriter(fn ($path) => (new LogKehadiranLengkapExport($admin))->writeTo($path));
        $this->assertCount(6, $this->dataRows($ws));
        $this->assertSame('Hadir', $this->cellText($ws, 'H9'));

        $this->withoutMiddleware(ThrottleRequests::class);
        $this->assertCount(2, $this->dataRows($this->perMahasiswa('admlogkehadiran', 'mhsB1@uji.test')));

        // Lewat HTTP (download asli)
        $ws = $this->xlsxFromResponse($this->actingAs($this->user('admin', 'admin2@uji.test'))->get('export/logkehadiran'));
        $this->assertCount(6, $this->dataRows($ws));
    }

    // Export per mahasiswa lewat HTTP sebagai admin
    private function perMahasiswa(string $uri, string $email)
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $admin = User::where('email', 'qa.admin@uji.test')->first() ?? $this->user('admin', 'qa.admin@uji.test');

        return $this->xlsxFromResponse($this->actingAs($admin)->get("$uri/export/$email"));
    }

    private function assertHeaderStandar($ws, array $header, string $kolomAkhir): void
    {
        $this->assertSame($header, $this->headerRow($ws));
        $this->assertSame('D9EAD3', $ws->getStyle('A3')->getFill()->getStartColor()->getRGB());
        $this->assertSame('D9EAD3', $ws->getStyle($kolomAkhir.'3')->getFill()->getStartColor()->getRGB());
        $this->assertTrue($ws->getStyle('A3')->getFont()->getBold());
        $this->assertSame('A4', $ws->getFreezePane());
        $this->assertNotSame('none', $ws->getStyle($kolomAkhir.'4')->getBorders()->getBottom()->getBorderStyle());
        $this->assertFalse($ws->getColumnDimension('B')->getAutoSize());
    }

    public function test_log_bulanan_header_standar_isi_dan_anti_formula(): void
    {
        $mhs = $this->d['mhsA1'];
        Logbulanan::where('email', $mhs->email)->where('bulan', 9)->update(['deskripsi' => '<p>Kegiatan <b>bank sampah</b></p>', 'nilai' => 90]);
        Logbulanan::where('email', $mhs->email)->where('bulan', 8)->update(['deskripsi' => '=HYPERLINK("http://x","klik")']);

        $ws = $this->xlsxFromWriter(fn ($path) => (new LogBulananLengkapExport($this->user('dpl', 'dplA@uji.test')))->writeTo($path));
        $this->assertHeaderStandar($ws, ['No.', 'Timestamp', 'Email Address', 'Nama Mahasiswa', 'Nomor Kontak', 'NIM', 'Nama Perguruan Tinggi', 'Bulan', 'Deskripsi', 'Nilai'], 'J');

        // Urut tahun, bulan: baris 4 = Agustus, baris 5 = September
        $this->assertSame('mhsA1@uji.test', $this->cellText($ws, 'C5'));
        $this->assertSame($mhs->nama, $this->cellText($ws, 'D5'));
        $this->assertSame($mhs->phone ?? '-', $this->cellText($ws, 'E5'));
        $this->assertSame($this->d['ptA']->nm_lemb, $this->cellText($ws, 'G5'));
        $this->assertSame('September 2026', $this->cellText($ws, 'H5'));
        $this->assertStringNotContainsString('<', $this->cellText($ws, 'I5'));
        $this->assertStringContainsString('bank sampah', $this->cellText($ws, 'I5'));
        $this->assertEquals(90, $ws->getCell('J5')->getValue());
        $this->assertSame('Agustus 2026', $this->cellText($ws, 'H4'));
        $this->assertCellIsText($ws, 'I4', '=HYPERLINK("http://x","klik")');
    }

    public function test_log_kehadiran_header_standar_isi_dan_anti_formula(): void
    {
        $mhs = $this->d['mhsA1'];
        Kehadiran::where('email', $mhs->email)->where('tanggal', '2026-09-05')->update(['waktu_masuk' => '2026-09-05 07:30:00', 'waktu_pulang' => null]);
        $mhs->update(['nama' => '=1+1']);

        $ws = $this->xlsxFromWriter(fn ($path) => (new LogKehadiranLengkapExport($this->user('dpl', 'dplA@uji.test')))->writeTo($path));
        $this->assertHeaderStandar($ws, ['No.', 'Email Address', 'Nama Mahasiswa', 'Nomor Kontak', 'NIM', 'Nama Perguruan Tinggi', 'Tanggal', 'Status Kehadiran', 'Jam Masuk', 'Jam Pulang'], 'J');

        // Urut tanggal: baris 4 = 05-08-2026, baris 5 = 05-09-2026
        $this->assertSame('mhsA1@uji.test', $this->cellText($ws, 'B5'));
        $this->assertCellIsText($ws, 'C5', '=1+1');
        $this->assertSame($mhs->phone ?? '-', $this->cellText($ws, 'D5'));
        $this->assertSame('05-09-2026', $this->cellText($ws, 'G5'));
        $this->assertSame('Hadir', $this->cellText($ws, 'H5'));
        $this->assertSame('07:30:00', $this->cellText($ws, 'I5'));
        $this->assertSame('-', $this->cellText($ws, 'J5'));
        $this->assertSame('05-08-2026', $this->cellText($ws, 'G4'));
    }

    public function test_export_kehadiran_per_mahasiswa_jam_kosong_jadi_strip(): void
    {
        Kehadiran::where('email', 'mhsB1@uji.test')->update(['waktu_masuk' => '2026-09-05 07:30:00', 'waktu_pulang' => null]);

        $ws = $this->perMahasiswa('admlogkehadiran', 'mhsB1@uji.test');

        foreach ([4, 5] as $baris) {
            $this->assertSame('07:30:00', $this->cellText($ws, "G$baris"));
            $this->assertSame('-', $this->cellText($ws, "H$baris"));
        }
    }
}
