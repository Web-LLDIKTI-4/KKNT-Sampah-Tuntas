<?php

namespace Tests\Feature\Mahasiswa;

use App\Exports\LogHarianByMhsExport;
use App\Models\Dplmentoring;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LogkegiatanTest extends TestCase
{
    use RefreshDatabase;

    private array $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

    private function payload(array $override = []): array
    {
        return $override + [
            'tanggal' => now()->toDateString(),
            'nama_kepala_keluarga' => 'Budi Santoso',
            'alamat_rumah' => 'Jl. Melati No. 5',
            'rt' => '003',
            'rw' => '07',
            'memilah' => '1',
            'organik_kg' => 2.5,
            'anorganik_kg' => 1.25,
            'residu_kg' => 0,
        ];
    }

    public function test_mahasiswa_can_create_log_with_generated_uuid(): void
    {
        $user = $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload())->assertJson(['success' => true]);

        $log = Logkegiatan::where('email', $user->email)->firstOrFail();
        $this->assertTrue(Str::isUuid($log->id_log));
        $this->assertSame('Budi Santoso', $log->nama_kepala_keluarga);
        $this->assertSame('003', $log->rt);
        $this->assertTrue($log->memilah);
        $this->assertSame(2.5, $log->organik_kg);
        $this->assertSame(1.25, $log->anorganik_kg);
        $this->assertSame(0.0, $log->residu_kg);
    }

    public function test_memilah_tidak_tersimpan_sebagai_false(): void
    {
        $user = $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload(['memilah' => '0']))->assertJson(['success' => true]);

        $this->assertFalse(Logkegiatan::where('email', $user->email)->firstOrFail()->memilah);
    }

    public function test_email_dari_input_diabaikan(): void
    {
        $user = $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload(['email' => 'lain@pps.test']))->assertJson(['success' => true]);

        $this->assertDatabaseHas('logkegiatan', ['email' => $user->email]);
        $this->assertDatabaseMissing('logkegiatan', ['email' => 'lain@pps.test']);
    }

    public function test_semua_field_wajib(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', [])->assertJson(['success' => false])
            ->assertJsonValidationErrors(['tanggal', 'nama_kepala_keluarga', 'alamat_rumah', 'rt', 'rw', 'memilah', 'organik_kg', 'anorganik_kg', 'residu_kg'], 'errors');
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload([
            'tanggal' => now()->addDay()->toDateString(),
            'rt' => '1/2',
            'rw' => '123456',
            'memilah' => 'mungkin',
            'organik_kg' => 'banyak',
            'anorganik_kg' => -1,
            'residu_kg' => 100000000,
            'nama_kepala_keluarga' => str_repeat('a', 151),
        ]))->assertJson(['success' => false])
            ->assertJsonValidationErrors(['tanggal', 'rt', 'rw', 'memilah', 'organik_kg', 'anorganik_kg', 'residu_kg', 'nama_kepala_keluarga'], 'errors');
    }

    public function test_teks_berawalan_formula_excel_ditolak(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload([
            'nama_kepala_keluarga' => '=HYPERLINK("http://x")',
            'alamat_rumah' => '@SUM(A1)',
        ]))->assertJsonValidationErrors(['nama_kepala_keluarga', 'alamat_rumah'], 'errors');
    }

    public function test_batas_atas_valid_diterima(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload([
            'rt' => '12A', 'rw' => 'XYZ99', 'organik_kg' => 99999999, 'nama_kepala_keluarga' => str_repeat('a', 150),
        ]))->assertJson(['success' => true]);
    }

    public function test_mahasiswa_cannot_touch_other_students_log(): void
    {
        $milikLain = Logkegiatan::factory()->create(['email' => 'lain@pps.test']);
        $this->loginAs('mahasiswa');

        $this->get('logkegiatan/edit/'.$milikLain->id_log)->assertNotFound();
        $this->put('logkegiatan/update', $this->payload(['id_log' => $milikLain->id_log]))->assertNotFound();
        $this->put('logkegiatan/destroy', ['id_log' => $milikLain->id_log])->assertNotFound();

        $this->assertDatabaseHas('logkegiatan', ['id_log' => $milikLain->id_log, 'nama_kepala_keluarga' => $milikLain->nama_kepala_keluarga]);
    }

    public function test_non_mahasiswa_tidak_bisa_insert(): void
    {
        $this->loginAs('dpl');

        $this->put('logkegiatan/insert', $this->payload())->assertRedirect();
        $this->assertDatabaseCount('logkegiatan', 0);
    }

    public function test_owner_can_update_and_delete(): void
    {
        $user = $this->loginAs('mahasiswa');
        $log = Logkegiatan::factory()->create(['email' => $user->email]);

        $this->put('logkegiatan/update', $this->payload(['id_log' => $log->id_log, 'rt' => '009', 'memilah' => '0']))
            ->assertJson(['success' => true]);
        $this->assertSame('009', $log->fresh()->rt);
        $this->assertFalse($log->fresh()->memilah);

        $this->put('logkegiatan/destroy', ['id_log' => $log->id_log])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('logkegiatan', ['id_log' => $log->id_log]);
    }

    public function test_form_tambah_dan_edit_tampil_tanpa_kpi(): void
    {
        $user = $this->loginAs('mahasiswa');
        $log = Logkegiatan::factory()->create(['email' => $user->email]);

        $this->get('logkegiatan/tambah')->assertOk()->assertSee('nama_kepala_keluarga')->assertDontSee('name="id_kpi"', false);
        $this->get('logkegiatan/edit/'.$log->id_log)->assertOk()->assertSee($log->alamat_rumah);
    }

    public function test_listdata_only_shows_own_logs_and_escapes_output(): void
    {
        $user = $this->loginAs('mahasiswa');
        Logkegiatan::factory()->create(['email' => $user->email, 'nama_kepala_keluarga' => '<script>alert(1)</script>']);
        Logkegiatan::factory()->create(['email' => 'lain@pps.test']);

        $nama = $this->getJson('logkegiatan/listdataserver?draw=1&start=0&length=10', $this->ajax)
            ->assertOk()->assertJsonPath('recordsTotal', 1)->json('data.0.nama_kepala_keluarga');

        $this->assertStringNotContainsString('<script>', $nama);
    }

    public function test_pt_tidak_melihat_identitas_rumah_tangga_di_tabel(): void
    {
        $sp = Satuanpendidikan::factory()->create();
        $this->loginAs('pt', ['email' => $sp->npsn]);
        $mhs = Mahasiswa::factory()->create(['kodept' => $sp->npsn]);
        Logkegiatan::factory()->create(['email' => $mhs->email, 'nama_kepala_keluarga' => 'Rahasia Warga', 'alamat_rumah' => 'Jl. Rahasia']);

        $row = $this->getJson("admlogkegiatan/listdataserver/{$mhs->email}?draw=1&start=0&length=10", $this->ajax)
            ->assertOk()->json('data.0');

        $this->assertSame('tidak ditampilkan', $row['nama_kepala_keluarga']);
        $this->assertSame('tidak ditampilkan', $row['alamat_rumah']);
    }

    public function test_admin_melihat_identitas_rumah_tangga(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create();
        Logkegiatan::factory()->create(['email' => $mhs->email, 'nama_kepala_keluarga' => 'Warga Terlihat']);

        $this->getJson("admlogkegiatan/listdataserver/{$mhs->email}?draw=1&start=0&length=10", $this->ajax)
            ->assertOk()->assertJsonPath('data.0.nama_kepala_keluarga', 'Warga Terlihat');
    }

    // Kolom 5 = nama kepala keluarga, 6 = alamat rumah
    private function assertExportIdentitas(string $email, string $nama, string $alamat): void
    {
        $this->get("admlogkegiatan/export/{$email}")->assertOk();

        // Nama file memuat detik berjalan; ambil export terakhir dari ExcelFake
        $downloads = (fn () => $this->downloads)->call(Excel::getFacadeRoot());
        $export = end($downloads);
        $this->assertInstanceOf(LogHarianByMhsExport::class, $export);
        $this->assertStringStartsWith('logharian_mahasiswa_', array_key_last($downloads));

        $row = $export->collection()->sole();
        $this->assertSame([$nama, $alamat, '003'], [$row[5], $row[6], $row[7]]);
    }

    public function test_export_pt_menyembunyikan_identitas_rumah_tangga(): void
    {
        Excel::fake();
        $sp = Satuanpendidikan::factory()->create();
        $this->loginAs('pt', ['email' => $sp->npsn]);
        $mhs = Mahasiswa::factory()->create(['kodept' => $sp->npsn]);
        Logkegiatan::factory()->create(['email' => $mhs->email, 'nama_kepala_keluarga' => 'Rahasia Warga', 'alamat_rumah' => 'Jl. Rahasia', 'rt' => '003']);

        $this->assertExportIdentitas($mhs->email, 'tidak ditampilkan', 'tidak ditampilkan');
    }

    public function test_export_admin_dan_dpl_menampilkan_identitas_rumah_tangga(): void
    {
        Excel::fake();
        $mhs = Mahasiswa::factory()->create();
        Logkegiatan::factory()->create(['email' => $mhs->email, 'nama_kepala_keluarga' => 'Warga Terlihat', 'alamat_rumah' => 'Jl. Terang', 'rt' => '003']);

        $this->loginAs('admin');
        $this->assertExportIdentitas($mhs->email, 'Warga Terlihat', 'Jl. Terang');

        $dpl = $this->loginAs('dpl');
        Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);
        $this->assertExportIdentitas($mhs->email, 'Warga Terlihat', 'Jl. Terang');
    }

    public function test_dpl_tabel_menampilkan_identitas_mahasiswa_bimbingan(): void
    {
        $dpl = $this->loginAs('dpl');
        $mhs = Mahasiswa::factory()->create();
        Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);
        Logkegiatan::factory()->create(['email' => $mhs->email, 'alamat_rumah' => 'Jl. Bimbingan']);

        $this->getJson("admlogkegiatan/listdataserver/{$mhs->email}?draw=1&start=0&length=10", $this->ajax)
            ->assertOk()->assertJsonPath('data.0.alamat_rumah', 'Jl. Bimbingan');
    }
}
