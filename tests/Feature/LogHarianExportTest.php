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
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LogHarianExportTest extends TestCase
{
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

    private function emailDiExport(User $user, ?string $query = ''): array
    {
        Excel::fake();
        Excel::matchByRegex();
        $this->actingAs($user)->get('logharian/export'.$query)->assertOk();

        $emails = [];
        Excel::assertDownloaded('/^log_harian_.+\.xlsx$/', function (LogHarianLengkapExport $export) use (&$emails) {
            $emails = $export->query()->pluck('l.email')->unique()->sort()->values()->all();

            return true;
        });

        return $emails;
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
        Excel::fake();
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

        Excel::fake();
        $this->actingAs($admin)->get('logharian/export?bulan=2026-08')->assertOk();
        Excel::matchByRegex();
        Excel::assertDownloaded('/^log_harian_2026-08_.+\.xlsx$/', fn (LogHarianLengkapExport $e) => $e->query()->count() === 1);

        foreach (['2026-13', 'abc', "2026-08' OR 1=1"] as $bulan) {
            $this->actingAs($admin)->get('logharian/export?bulan='.urlencode($bulan))->assertSessionHasErrors('bulan');
        }
    }

    public function test_guest_ditolak(): void
    {
        $this->get('logharian/export')->assertRedirect(route('login'));
    }

    public function test_kolom_header_kuning_log_harian_dan_anti_formula(): void
    {
        $mhs = $this->d['mhsA1'];
        Logkegiatan::where('email', $mhs->email)->update(['deskripsi' => '=HYPERLINK("http://x","klik")']);

        $sheet = function (User $user) {
            $path = tempnam(sys_get_temp_dir(), 'log').'.xlsx';
            file_put_contents($path, Excel::raw(new LogHarianLengkapExport($user), ExcelType::XLSX));
            $ws = IOFactory::load($path)->getActiveSheet();
            @unlink($path);

            return $ws;
        };

        $ws = $sheet($this->user('dpl', 'dplA@uji.test'));
        $this->assertSame([
            'Timestamp', 'Email Address', 'Nama Mahasiswa Penginput Data', 'Nomor Kontak', 'Tanggal',
            'Kabupaten/Kota', 'Nama Kecamatan', 'Nama Kelurahan/Desa', 'Deskripsi Kegiatan',
            'Volume/Kuantitas Output', 'Satuan', 'Aktivitas', 'Tautan Bukti',
        ], $ws->rangeToArray('A1:M1')[0]);
        $this->assertSame('FFFF00', $ws->getStyle('M1')->getFill()->getStartColor()->getRGB());
        $this->assertTrue($ws->getStyle('A1')->getFont()->getBold());
        $this->assertNotSame('none', $ws->getStyle('M2')->getBorders()->getBottom()->getBorderStyle());
        $this->assertSame('mhsA1@uji.test', $ws->getCell('B2')->getValue());
        $this->assertSame($mhs->nama, $ws->getCell('C2')->getValue());
        $this->assertSame('05/09/2026', $ws->getCell('E2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $ws->getCell('I2')->getDataType());
        $this->assertStringStartsWith('=HYPERLINK', $ws->getCell('I2')->getValue());

        $this->assertSame($mhs->phone, $ws->getCell('D2')->getValue());
        $this->assertSame(16, (int) $ws->getColumnDimension('D')->getWidth());
        $this->assertFalse($ws->getColumnDimension('B')->getAutoSize());

    }

    public function test_email_dan_kontak_mahasiswa_tampil_untuk_semua_role(): void
    {
        $mhs = $this->d['mhsA1'];
        $users = ['admin' => 'admin@uji.test', 'kepala' => 'kepala@uji.test', 'pemda' => 'pemda@uji.test',
            'pt' => $this->d['ptA']->npsn, 'dpl' => 'dplA@uji.test', 'mahasiswa' => 'mhsA1@uji.test'];

        foreach ($users as $role => $email) {
            $export = new LogHarianLengkapExport($this->user($role, $email), '2026-09');
            $r = $export->map($export->query()->where('l.email', $mhs->email)->first());
            $this->assertSame($mhs->email, $r[1], $role);
            $this->assertSame($mhs->phone, $r[3], $role);
            $this->assertSame($mhs->nama, $r[2], $role);
        }
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
