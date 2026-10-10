<?php

namespace Tests\Feature;

use App\Models\CapaianKegiatan;
use App\Models\KategoriKegiatan;
use App\Models\Logkegiatan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

// Tanpa RefreshDatabase: DDL MySQL auto-commit, jadi data uji dibersihkan manual dan skema selalu dikembalikan ke up()
class RenameKategoriKegiatanMigrationTest extends TestCase
{
    private Migration $migration;

    private string $email;

    private bool $dropDuplicateKpi = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--force' => true]);
        $this->migration = require database_path('migrations/2026_10_10_000004_rename_kpi_to_kategori_kegiatan.php');
        $this->email = 'migrasi-'.Str::lower(Str::random(8)).'@pps.test';
    }

    protected function tearDown(): void
    {
        if ($this->dropDuplicateKpi) {
            Schema::dropIfExists('kpi');
        }

        // Pastikan skema kembali ke nama baru walau assertion gagal di tengah
        $this->migration->up();
        DB::table('capaian_kegiatan')->where('email', $this->email)->delete();
        DB::table('logkegiatan')->where('email', $this->email)->delete();
        DB::table('kategori_kegiatan')->where('nama_kategori', 'like', 'Migrasi Uji%')->delete();

        parent::tearDown();
    }

    private function seedData(): array
    {
        $kategori = KategoriKegiatan::create(['nama_kategori' => 'Migrasi Uji Kategori']);
        $capaian = CapaianKegiatan::factory()->create(['email' => $this->email, 'id_kategori' => $kategori->id_kategori]);
        $log = Logkegiatan::factory()->create(['email' => $this->email, 'id_kategori' => $kategori->id_kategori]);

        return [$kategori->id_kategori, $capaian->id_capaian, $log->id_log];
    }

    public function test_down_lalu_up_menjaga_data_dan_nama_skema(): void
    {
        [$idKategori, $idCapaian, $idLog] = $this->seedData();

        $this->migration->down();

        foreach (['kpi', 'kpi_capaian', 'kpi_sampah'] as $tabel) {
            $this->assertTrue(Schema::hasTable($tabel), "down: $tabel");
        }
        foreach (['kategori_kegiatan', 'capaian_kegiatan', 'pengurangan_sampah'] as $tabel) {
            $this->assertFalse(Schema::hasTable($tabel), "down: $tabel masih ada");
        }
        $this->assertTrue(Schema::hasColumns('kpi', ['id_kpi', 'nama_kpi']));
        $this->assertTrue(Schema::hasColumn('kpi_capaian', 'id_kpi'));
        $this->assertTrue(Schema::hasColumn('logkegiatan', 'id_kpi'));
        $this->assertFalse(Schema::hasColumn('logkegiatan', 'id_kategori'));
        $this->assertTrue(Schema::hasIndex('logkegiatan', 'logkegiatan_id_kpi_index'));
        $this->assertTrue(Schema::hasIndex('kpi_capaian', 'kpi_capaian_email_bulan_unique'));

        $this->assertSame('Migrasi Uji Kategori', DB::table('kpi')->where('id_kpi', $idKategori)->value('nama_kpi'));
        $this->assertSame($idKategori, DB::table('kpi_capaian')->where('id_capaian', $idCapaian)->value('id_kpi'));
        $this->assertSame($idKategori, DB::table('logkegiatan')->where('id_log', $idLog)->value('id_kpi'));

        $this->migration->up();

        $this->assertTrue(Schema::hasColumns('kategori_kegiatan', ['id_kategori', 'nama_kategori']));
        $this->assertFalse(Schema::hasTable('kpi'));
        $this->assertFalse(Schema::hasColumn('logkegiatan', 'id_kpi'));
        $this->assertTrue(Schema::hasIndex('logkegiatan', 'logkegiatan_id_kategori_index'));
        $this->assertTrue(Schema::hasIndex('capaian_kegiatan', 'capaian_kegiatan_email_bulan_unique'));
        $this->assertTrue(Schema::hasIndex('pengurangan_sampah', 'pengurangan_sampah_email_bulan_unique'));

        $this->assertSame('Migrasi Uji Kategori', KategoriKegiatan::find($idKategori)?->nama_kategori);
        $this->assertSame($idKategori, CapaianKegiatan::find($idCapaian)?->kategoriKegiatan?->id_kategori);
        $this->assertSame($idKategori, Logkegiatan::find($idLog)?->kategoriKegiatan?->id_kategori);
    }

    public function test_up_dan_down_idempoten_bila_dijalankan_ulang(): void
    {
        [$idKategori] = $this->seedData();

        $this->migration->up();
        $this->migration->up();
        $this->assertSame(1, KategoriKegiatan::whereKey($idKategori)->count());

        $this->migration->down();
        $this->migration->down();
        $this->assertSame(1, DB::table('kpi')->where('id_kpi', $idKategori)->count());

        $this->migration->up();
        $this->assertSame(1, KategoriKegiatan::whereKey($idKategori)->count());
    }

    public function test_unique_email_bulan_tetap_berlaku_setelah_rename(): void
    {
        $this->seedData();
        $capaian = CapaianKegiatan::where('email', $this->email)->sole();

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        CapaianKegiatan::factory()->create(['email' => $this->email, 'bulan' => $capaian->bulan]);
    }

    public function test_up_gagal_bila_tabel_lama_dan_baru_sama_sama_ada(): void
    {
        $this->dropDuplicateKpi = true;
        Schema::create('kpi', fn (\Illuminate\Database\Schema\Blueprint $table) => $table->id('id_kpi'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("'kpi' dan 'kategori_kegiatan' sama-sama ada");
        $this->migration->up();
    }
}
