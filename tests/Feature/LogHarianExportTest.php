<?php

namespace Tests\Feature;

use App\Exports\LogHarianLengkapExport;
use App\Models\Desa;
use App\Models\Dplmentoring;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\Concerns\ReadsXlsx;
use Tests\TestCase;

class LogHarianExportTest extends TestCase
{
    use ReadsXlsx;
    use RefreshDatabase;

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
            Logkegiatan::factory()->create(['email' => $mhs->email, 'tanggal' => '2026-09-05', 'deskripsi' => 'Kegiatan '.$key]);
            $this->d[$key] = $mhs;
        }
        Dplmentoring::create(['email_mahasiswa' => 'mhsA1@uji.test', 'email_dpl' => 'dplA@uji.test']);
    }

    // Pemilik baris diambil dari deskripsi "Kegiatan <key>" (mode mahasiswa tidak punya kolom email)
    private function emailDiExport(User $user, ?string $query = ''): array
    {
        $ws = $this->xlsxFromResponse($this->actingAs($user)->get('logharian/export'.$query), '/filename=log_aktivitas_.+\.xlsx/');
        $kolom = array_search('Deskripsi Kegiatan', $this->headerRow($ws), true);

        return collect($this->dataColumn($ws, $kolom))
            ->map(fn ($deskripsi) => str_replace('Kegiatan ', '', $deskripsi).'@uji.test')
            ->unique()->sort()->values()->all();
    }

    private function sheet(LogHarianLengkapExport $export)
    {
        return $this->xlsxFromWriter(fn ($path) => $export->writeTo($path));
    }

    private function user(string $role, string $email): User
    {
        return User::factory()->role($role)->create(['email' => $email]);
    }

    public function test_admin_kepala_pemda_export_semua_data_per_bulan(): void
    {
        foreach (['admin', 'kepala', 'pemda'] as $role) {
            $this->assertSame(['mhsA1@uji.test', 'mhsA2@uji.test', 'mhsB1@uji.test'], $this->emailDiExport($this->user($role, $role.'@uji.test'), '?bulan=2026-09'), $role);
        }
    }

    public function test_bulan_opsional_untuk_semua_role(): void
    {
        foreach (['admin', 'kepala', 'pemda'] as $role) {
            $this->assertSame(['mhsA1@uji.test', 'mhsA2@uji.test', 'mhsB1@uji.test'], $this->emailDiExport($this->user($role, $role.'@uji.test')), $role);
        }
        // bulan= kosong dari form diperlakukan sebagai semua data
        $this->assertSame(['mhsA1@uji.test', 'mhsA2@uji.test'], $this->emailDiExport($this->user('pt', $this->d['ptA']->npsn), '?bulan='));
        $this->assertSame(['mhsA1@uji.test'], $this->emailDiExport($this->user('dpl', 'dplA@uji.test')));
        $this->assertSame(['mhsA1@uji.test'], $this->emailDiExport($this->user('mahasiswa', 'mhsA1@uji.test')));
    }

    public function test_throttle_3_per_menit_dan_route_lama_dihapus(): void
    {
        $admin = $this->user('admin', 'admin@uji.test');
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($admin)->get('logharian/export?bulan=2026-09')->assertOk();
        }
        $this->actingAs($admin)->get('logharian/export?bulan=2026-09')->assertStatus(429);

        $this->actingAs($this->user('mahasiswa', 'mhsA1@uji.test'))->get('logkegiatan/export')->assertNotFound();
    }

    public function test_admlogharian_export_redirect_ke_logharian_export(): void
    {
        $this->actingAs($this->user('admin', 'admin@uji.test'));
        $this->get('admlogharian/export?bulan=2026-09&email=mhsB1@uji.test')->assertRedirect(route('logharian.export', ['bulan' => '2026-09']));
        $this->get('admlogharian/export')->assertRedirect(route('logharian.export'));
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

    public function test_mahasiswa_hanya_data_sendiri_idor_gagal(): void
    {
        $user = $this->user('mahasiswa', 'mhsA2@uji.test');

        $this->assertSame(['mhsA2@uji.test'], $this->emailDiExport($user));
        // Parameter email/id milik orang lain diabaikan
        $this->assertSame(['mhsA2@uji.test'], $this->emailDiExport($user, '?email=mhsB1@uji.test&id_mahasiswa='.$this->d['mhsB1']->id_mahasiswa));
    }

    public function test_filter_bulan_dan_validasi(): void
    {
        // Lebih dari 3 request: throttle diuji terpisah
        $this->withoutMiddleware(ThrottleRequests::class);
        Logkegiatan::factory()->create(['email' => 'mhsA2@uji.test', 'tanggal' => '2026-08-31']);
        $admin = $this->user('admin', 'admin@uji.test');

        $ws = $this->xlsxFromResponse($this->actingAs($admin)->get('logharian/export?bulan=2026-08'), '/filename=log_aktivitas_2026-08_.+\.xlsx/');
        $this->assertCount(1, $this->dataRows($ws));
        $this->assertSame('mhsA2@uji.test', $this->cellText($ws, 'C4'));

        foreach (['2026-13', 'abc', "2026-08' OR 1=1"] as $bulan) {
            $this->actingAs($admin)->get('logharian/export?bulan='.urlencode($bulan))->assertSessionHasErrors('bulan');
        }
    }

    public function test_guest_ditolak(): void
    {
        $this->get('logharian/export')->assertRedirect(route('login'));
    }

    public function test_kolom_header_standar_log_harian_dan_anti_formula(): void
    {
        $mhs = $this->d['mhsA1'];
        Logkegiatan::where('email', $mhs->email)->update(['deskripsi' => '=HYPERLINK("http://x","klik")']);

        $ws = $this->sheet(new LogHarianLengkapExport($this->user('dpl', 'dplA@uji.test')));
        $this->assertSame([
            'No.', 'Timestamp', 'Email Address', 'Nama Mahasiswa Penginput Data', 'Nomor Kontak', 'Tanggal',
            'Kabupaten/Kota', 'Nama Kecamatan', 'Nama Kelurahan/Desa', 'Kategori Kegiatan', 'Nama Aktivitas',
            'Deskripsi Kegiatan', 'Volume/Kuantitas Output', 'Satuan', 'Tautan Bukti',
        ], $this->headerRow($ws));
        $this->assertSame('O', $ws->getHighestColumn());
        $this->assertSame('D9EAD3', $ws->getStyle('O3')->getFill()->getStartColor()->getRGB());
        $this->assertTrue($ws->getStyle('A3')->getFont()->getBold());
        $this->assertNotSame('none', $ws->getStyle('O4')->getBorders()->getBottom()->getBorderStyle());
        $this->assertSame('mhsA1@uji.test', $this->cellText($ws, 'C4'));
        $this->assertSame($mhs->nama, $this->cellText($ws, 'D4'));
        $this->assertSame('05-09-2026', $this->cellText($ws, 'F4'));
        $this->assertCellIsText($ws, 'L4', '=HYPERLINK("http://x","klik")');

        $this->assertSame($mhs->phone ?? '-', $this->cellText($ws, 'E4'));
        $this->assertSame(16, (int) $ws->getColumnDimension('E')->getWidth());
        $this->assertFalse($ws->getColumnDimension('B')->getAutoSize());
    }

    public function test_email_dan_kontak_mahasiswa_tampil_untuk_role_selain_mahasiswa(): void
    {
        $mhs = $this->d['mhsA1'];
        $users = ['admin' => 'admin@uji.test', 'kepala' => 'kepala@uji.test', 'pemda' => 'pemda@uji.test',
            'pt' => $this->d['ptA']->npsn, 'dpl' => 'dplA@uji.test'];

        foreach ($users as $role => $email) {
            $ws = $this->sheet(new LogHarianLengkapExport($this->user($role, $email), '2026-09'));
            $r = collect($this->dataRows($ws))->firstWhere(2, $mhs->email);
            $this->assertNotNull($r, $role);
            $this->assertSame($mhs->phone ?? '-', $r[4], $role);
            $this->assertSame($mhs->nama, $r[3], $role);
        }
    }

    public function test_mahasiswa_hanya_kolom_tabel_tanpa_identitas_dan_lokasi(): void
    {
        $mhs = $this->d['mhsA1'];
        Logkegiatan::where('email', $mhs->email)->update(['deskripsi' => '=HYPERLINK("http://x","klik")']);

        $ws = $this->sheet(new LogHarianLengkapExport($this->user('mahasiswa', $mhs->email)));
        $header = ['No.', 'Tanggal', 'Kategori Kegiatan', 'Nama Aktivitas', 'Deskripsi Kegiatan', 'Volume/Kuantitas Output', 'Satuan', 'Tautan Bukti'];
        $this->assertSame('H', $ws->getHighestColumn());
        $this->assertSame($header, $this->headerRow($ws));
        $rows = $this->dataRows($ws);
        $this->assertCount(1, $rows);
        $this->assertEquals(1, $rows[0][0]);
        $this->assertSame('05-09-2026', $rows[0][1]);
        $this->assertNotContains($mhs->email, $rows[0]);
        $this->assertNotContains($mhs->nama, $rows[0]);
        if ($mhs->phone) {
            $this->assertNotContains($mhs->phone, $rows[0]);
        }
        $this->assertSame('D9EAD3', $ws->getStyle('H3')->getFill()->getStartColor()->getRGB());
        $this->assertSame('none', $ws->getStyle('I3')->getFill()->getFillType());
        $this->assertCellIsText($ws, 'E4', '=HYPERLINK("http://x","klik")');
        $this->assertSame(45, (int) $ws->getColumnDimension('E')->getWidth());

        $ws = $this->sheet(new LogHarianLengkapExport($this->user('admin', 'admin@uji.test')));
        $this->assertCount(15, $this->headerRow($ws));
        $this->assertSame(['Timestamp', 'Email Address', 'Nama Mahasiswa Penginput Data', 'Nomor Kontak'], array_slice($this->headerRow($ws), 1, 4));
        $this->assertCount(15, $this->dataRows($ws)[0]);
    }

    public function test_tombol_export_ada_di_halaman_log_harian(): void
    {
        $this->actingAs($this->user('mahasiswa', 'mhsA1@uji.test'));
        $this->get('logkegiatan/listdata')->assertOk()->assertSee(route('logharian.export'), false);

        // 3 halaman log: tombol "Export Semua" + input bulan opsional
        $halaman = [
            'admlogkegiatan' => route('logharian.export'),
            'admlogbulanan' => route('export.logbulanan'),
            'admlogkehadiran' => route('export.logkehadiran'),
        ];
        $users = ['admin' => 'admin@uji.test', 'kepala' => 'kepala@uji.test', 'pemda' => 'pemda@uji.test',
            'pt' => $this->d['ptA']->npsn, 'dpl' => 'dplA@uji.test'];

        foreach ($users as $role => $email) {
            $this->actingAs($this->user($role, $email));
            foreach ($halaman as $uri => $action) {
                $html = $this->get($uri)->assertOk()->assertSee('Export Semua')->getContent();
                $this->assertStringContainsString('action="'.$action.'"', $html, "$role $uri");
                $this->assertMatchesRegularExpression('/<input type="month" name="bulan"(?![^>]*required)[^>]*>/', $html, "$role $uri");
            }
        }
    }

    public function test_admlogharian_legacy_redirect_ke_admlogkegiatan(): void
    {
        $this->actingAs($this->user('admin', 'admin@uji.test'));

        $this->get('admlogharian')->assertRedirect('admlogkegiatan');
        foreach (['admlogharian/listdata', 'admlogharian/listdataserver', 'admlogharian/permhs/x%40uji.test', 'admlogharian/permhsserver/x%40uji.test'] as $uri) {
            $this->get($uri)->assertNotFound();
        }
    }
}
