<?php

namespace Tests\Feature;

use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\Kehadiran;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Logkegiatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\PendataanPemilahanSampah;
use App\Models\Pjdesa;
use App\Models\RencanaKerja;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\AttendanceService;
use Database\Seeders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

// Seeder per modul (urutan 1–14): berurutan, idempotent, cek prasyarat, DatabaseSeeder penuh
class SeederSplitTest extends TestCase
{
    use RefreshDatabase;

    private const DOMAIN = '@kknt.test';

    private const PT = 12;

    private const KELOMPOK = self::PT * 3;

    // ketua + 4 anggota
    private const ANGGOTA_KELOMPOK = self::KELOMPOK * 5;

    private const SEEDERS = [
        Seeders\WilayahSeeder::class,
        Seeders\KpiMasterSeeder::class,
        Seeders\AkunPimpinanSeeder::class,
        Seeders\PerguruanTinggiSeeder::class,
        Seeders\DplSeeder::class,
        Seeders\MahasiswaSeeder::class,
        Seeders\KehadiranSeeder::class,
        Seeders\LogHarianSeeder::class,
        Seeders\LogBulananSeeder::class,
        Seeders\KpiCapaianSeeder::class,
        Seeders\PendataanPemilahanSeeder::class,
        Seeders\RencanaKerjaSeeder::class,
        Seeders\PenilaianSeeder::class,
        Seeders\EvaluasiSaranSeeder::class,
    ];

    // Tabel yang diisi seeder; dipakai untuk cek idempotent
    private const TABLES = [
        'users', 'lokasi_program', 'kecamatan', 'desa', 'kpi', 'ref_satuanpendidikan', 'dpl', 'mahasiswa',
        'pj_desa', 'mahasiswa_lokasi', 'dpl_mentoring', 'kehadiran', 'logkegiatan', 'logkegiatan_bulanan',
        'kpi_capaian', 'kpi_sampah', 'pendataan_pemilahan_sampah', 'rencana_kerja', 'tugasakhir',
        'nilai_konversi', 'nilai_freeform', 'dpl_laporan_bulanan', 'evaluasi_kegiatan', 'evaluasi_kegiatan_jawaban', 'saran',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.seed_password' => 'password-uji-123', 'app.seed_admin_email' => null]);
        Storage::fake(RencanaKerja::DISK);
    }

    private function runSeeder(string $class): void
    {
        $this->app->make($class)->setContainer($this->app)->__invoke();
    }

    private function runAll(): void
    {
        foreach (self::SEEDERS as $class) {
            $this->runSeeder($class);
        }
    }

    private function counts(): array
    {
        return collect(self::TABLES)->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
    }

    private function kelompokEmails()
    {
        return Mahasiswa::where('email', 'like', 'ketua.%'.self::DOMAIN)
            ->orWhere('email', 'like', 'mhs_.%'.self::DOMAIN)
            ->pluck('email');
    }

    public function test_seeder_berurutan_menghasilkan_data_terhubung(): void
    {
        $this->runAll();

        // Master & akun pimpinan
        $this->assertSame(3, LokasiProgram::count());
        $this->assertSame(12, DB::table('desa')->count());
        $this->assertSame(1, Kpi::count());
        foreach (['admin', 'kepala', 'pemda'] as $role) {
            $this->assertSame(1, User::where('role', $role)->count(), $role);
        }
        $this->assertTrue(Hash::check('password-uji-123', User::where('role', 'admin')->value('password')));

        // PT: akun login NPSN, 1 PT = 1 kelurahan
        $this->assertSame(self::PT, Satuanpendidikan::count());
        $this->assertSame(self::PT, User::where('role', 'pt')->whereIn('email', Satuanpendidikan::pluck('npsn'))->count());
        $this->assertSame(self::PT, Dpl::count());
        $this->assertSame(0, Dpl::whereNotIn('kodept', Satuanpendidikan::select('npsn'))->count());
        $this->assertSame(Dpl::count(), User::where('role', 'dpl')->whereIn('email', Dpl::pluck('email'))->count());

        // Mahasiswa kelompok: lokasi + dplmentoring ke DPL PT-nya + akun
        $kelompok = $this->kelompokEmails();
        $this->assertCount(self::ANGGOTA_KELOMPOK, $kelompok);
        $this->assertSame(0, Mahasiswa::whereIn('email', $kelompok)->doesntHave('dplmentoring')->count());
        $this->assertSame(0, Mahasiswa::whereIn('email', $kelompok)
            ->whereNotExists(fn ($q) => $q->from('mahasiswa_lokasi as ml')->whereColumn('ml.id_mahasiswa', 'mahasiswa.id_mahasiswa')->whereNotNull('ml.id_desa'))
            ->count());
        $this->assertSame(0, DB::table('dpl_mentoring as dm')
            ->join('mahasiswa as m', 'm.email', '=', 'dm.email_mahasiswa')
            ->join('dpl as d', 'd.email', '=', 'dm.email_dpl')
            ->whereColumn('m.kodept', '<>', 'd.kodept')->count(), 'DPL beda PT');
        $this->assertSame(Mahasiswa::count(), User::where('role', 'mahasiswa')->whereIn('email', Mahasiswa::pluck('email'))->count());
        $this->assertSame(2, Mahasiswa::where('email', 'like', 'belumlokasi%')->count());
        $this->assertSame(self::PT * 2, Mahasiswa::where('email', 'like', 'sebaran%')->count());

        // Ketua: pj_desa + tepat 1 capaian bulan ini
        $this->assertSame(self::KELOMPOK, Pjdesa::count());
        $this->assertSame(0, Pjdesa::where('email', 'not like', 'ketua.%')->count());
        $this->assertSame(self::KELOMPOK, Kpicapaian::count());
        $this->assertSame(0, Pjdesa::whereNotIn('email', Kpicapaian::select('email'))->count(), 'ketua tanpa capaian');
        $this->assertSame(1, (int) DB::table('kpi_capaian')->selectRaw('COUNT(*) n')->groupBy('email', 'bulan')->get()->max('n'));

        // Kehadiran: status valid, termasuk kuliah; log harian tidak di hari blocking
        $status = Kehadiran::distinct()->pluck('status_kehadiran')->all();
        $this->assertEmpty(array_diff($status, array_merge(['hadir'], AttendanceService::IZIN_STATUSES)));
        $this->assertContains('kuliah', $status);
        $this->assertSame(0, Kehadiran::whereNotIn('email', Mahasiswa::select('email'))->count());
        $this->assertSame(0, DB::table('logkegiatan as l')
            ->join('kehadiran as k', fn ($j) => $j->on('k.email', '=', 'l.email')->on('k.tanggal', '=', 'l.tanggal'))
            ->whereIn('k.status_kehadiran', AttendanceService::BLOCKING_STATUSES)->count(), 'log di hari blocking');
        $this->assertSame(0, Logkegiatan::whereNull('deskripsi')->count());
        $this->assertSame(0, Logkegiatan::whereNotIn('email', Mahasiswa::select('email'))->count());
        $this->assertGreaterThan(0, Logkegiatan::count());
        $this->assertGreaterThan(0, DB::table('logkegiatan_bulanan')->count());

        // Pemilahan & rencana kerja
        $this->assertGreaterThan(0, PendataanPemilahanSampah::count());
        $this->assertSame(0, PendataanPemilahanSampah::where('email', 'not like', '%'.self::DOMAIN)->count());
        // Data sampah 3 bulan terakhir (sumber rekap KpiSampahService); kpi_sampah tidak di-seed
        $this->assertSame(0, PendataanPemilahanSampah::where('tanggal', '<', now()->subMonthsNoOverflow(3)->startOfMonth()->toDateString())
            ->orWhere('tanggal', '>', today()->toDateString())->count());
        $this->assertGreaterThanOrEqual(2, PendataanPemilahanSampah::selectRaw("DATE_FORMAT(tanggal, '%Y-%m') ym")->distinct()->pluck('ym')->count());
        $this->assertSame(0, DB::table('kpi_sampah')->count());
        $this->assertSame(0, PendataanPemilahanSampah::whereNotIn('email', Mahasiswa::select('email'))->count());
        $this->assertGreaterThan(0, RencanaKerja::count());
        $this->assertSame(0, RencanaKerja::whereNotIn('kodept', Satuanpendidikan::select('npsn'))->count());
        $this->assertSame(0, RencanaKerja::whereNotNull('uploaded_by')->whereNotIn('uploaded_by', User::select('id'))->count());
        // File rencana kerja harus ada agar tombol unduh tidak 404
        foreach (RencanaKerja::whereNotNull('file_path')->pluck('file_path') as $path) {
            $this->assertTrue(Storage::disk(RencanaKerja::DISK)->exists($path), "file $path tidak ada");
        }

        // Penilaian & evaluasi mengacu DPL/PT yang ada
        $this->assertSame(0, DB::table('tugasakhir')->whereNotIn('email_dpl', Dpl::select('email'))->count());
        $this->assertSame(0, DB::table('nilai_konversi')->whereNotIn('id_mahasiswa', Mahasiswa::select('id_mahasiswa'))->count());
        $this->assertSame(0, DB::table('dpl_laporan_bulanan')->whereNotIn('email', Dpl::select('email'))->count());
        $this->assertGreaterThan(0, DB::table('evaluasi_kegiatan_jawaban')->count());
        $this->assertSame(0, DB::table('evaluasi_kegiatan_jawaban')->whereNotIn('kodept', Satuanpendidikan::select('npsn'))->count());
    }

    public function test_dijalankan_dua_kali_tidak_menambah_baris_dan_data_asli_utuh(): void
    {
        $asli = User::factory()->role('mahasiswa')->create(['email' => 'asli@kampus.ac.id', 'password' => Hash::make('rahasia-asli')]);
        $mhsAsli = Mahasiswa::factory()->create(['email' => $asli->email, 'nama' => 'Mahasiswa Asli']);

        $this->runAll();
        $pertama = $this->counts();
        $this->runAll();

        $this->assertSame($pertama, $this->counts());
        $this->assertTrue(Hash::check('rahasia-asli', $asli->fresh()->password));
        $this->assertSame('Mahasiswa Asli', $mhsAsli->fresh()->nama);
        $this->assertSame(0, Logkegiatan::where('email', $asli->email)->count());
    }

    public function test_tiap_seeder_dijalankan_ulang_sendiri_tidak_duplikat(): void
    {
        $this->runAll();
        $awal = $this->counts();

        foreach (self::SEEDERS as $class) {
            $this->runSeeder($class);
            $this->assertSame($awal, $this->counts(), class_basename($class).' menambah baris saat dijalankan ulang');
        }
    }

    public function test_hasil_deterministik(): void
    {
        // Run pemanasan: run pertama setelah test lain di proses yang sama bisa beda (lihat qa-done.md)
        $this->runAll();
        $this->refreshTestDatabaseForSeed();
        $this->runAll();
        $nama = Mahasiswa::orderBy('email')->pluck('nama', 'email')->all();

        $this->refreshTestDatabaseForSeed();
        $this->runAll();

        $this->assertSame($nama, Mahasiswa::orderBy('email')->pluck('nama', 'email')->all());
    }

    // Seeder tanpa prasyarat (urutan 1–3) dikecualikan
    public static function seederBerprasyarat(): array
    {
        return collect(array_slice(self::SEEDERS, 3))
            ->mapWithKeys(fn ($c) => [class_basename($c) => [$c]])
            ->all();
    }

    #[DataProvider('seederBerprasyarat')]
    public function test_prasyarat_kosong_melempar_runtime_exception(string $class): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Jalankan \w+Seeder dulu/');

        $this->runSeeder($class);
    }

    public function test_dpl_seeder_tanpa_pt_melempar_exception_dan_tidak_menulis_data(): void
    {
        $this->runSeeder(Seeders\WilayahSeeder::class);
        $this->runSeeder(Seeders\KpiMasterSeeder::class);
        $this->runSeeder(Seeders\AkunPimpinanSeeder::class);

        try {
            $this->runSeeder(Seeders\DplSeeder::class);
            $this->fail('DplSeeder harus gagal tanpa PerguruanTinggiSeeder');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('PerguruanTinggiSeeder', $e->getMessage());
        }
        $this->assertSame(0, Dpl::count());
        $this->assertSame(0, User::where('role', 'dpl')->count());
    }

    public function test_database_seeder_penuh(): void
    {
        $this->seed(Seeders\DatabaseSeeder::class);

        $this->assertSame(self::PT, Satuanpendidikan::count());
        $this->assertCount(self::ANGGOTA_KELOMPOK, $this->kelompokEmails());
        $this->assertSame(self::KELOMPOK, Kpicapaian::count());
        $this->assertGreaterThan(0, PendataanPemilahanSampah::count());
        $this->assertGreaterThan(0, RencanaKerja::count());
        foreach (['SimulasiSeeder', 'ActivitySeeder', 'MasterDataSeeder', 'UserSeeder', 'AdminSeeder'] as $lama) {
            $this->assertFalse(class_exists('Database\\Seeders\\'.$lama), "$lama seharusnya dihapus");
        }
    }

    // Kosongkan tabel seed di dalam transaksi RefreshDatabase
    private function refreshTestDatabaseForSeed(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (self::TABLES as $t) {
            DB::table($t)->delete();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
