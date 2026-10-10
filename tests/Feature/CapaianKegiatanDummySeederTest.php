<?php

namespace Tests\Feature;

use App\Models\KategoriKegiatan;
use App\Models\CapaianKegiatan;
use App\Models\Pjdesa;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\CapaianKegiatanDummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CapaianKegiatanDummySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_capaian_kegiatan_dummy_data_and_is_rerunnable(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, KategoriKegiatan::count());
        $this->assertSame('Pengurangan Sampah Rumah Tangga', KategoriKegiatan::first()->nama_kategori);
        $this->assertSame(12 * 3, Pjdesa::where('email', 'like', '%@kknt.test')->count());
        $this->assertTrue(CapaianKegiatan::where('status_capaian', 'Y')->exists());
        $this->assertSame(12 * 3, CapaianKegiatan::count());

        // Dijalankan ulang tidak menggandakan data maupun menambah ketua
        $jumlah = CapaianKegiatan::count();
        $this->seed(CapaianKegiatanDummySeeder::class);
        $this->assertSame($jumlah, CapaianKegiatan::count());
        $this->assertSame(1, KategoriKegiatan::count());
        $this->assertSame(12 * 3, Pjdesa::count());

        $this->assertOnePtPerKelurahan();
    }

    // Penempatan mahasiswa tahun ini: setiap PT hanya di 1 kelurahan dan setiap kelurahan hanya 1 PT
    private function assertOnePtPerKelurahan(): void
    {
        $penempatan = DB::table('mahasiswa_lokasi as ml')
            ->join('mahasiswa as m', 'm.id_mahasiswa', '=', 'ml.id_mahasiswa')
            ->where('ml.tahun', now()->year)
            ->select('m.kodept', 'ml.id_desa')
            ->distinct()
            ->get();

        $this->assertSame(12, $penempatan->pluck('kodept')->unique()->count());
        $this->assertTrue($penempatan->groupBy('kodept')->every(fn ($rows) => $rows->count() === 1));
        $this->assertTrue($penempatan->groupBy('id_desa')->every(fn ($rows) => $rows->count() === 1));

        $this->assertSame(0, DB::table('mahasiswa_lokasi')->where('tahun', '<>', now()->year)->count());
    }

    public function test_each_pt_has_three_ketua_with_one_report_per_month(): void
    {
        $this->seed(DatabaseSeeder::class);

        $ketuaPerPt = DB::table('pj_desa as p')
            ->join('mahasiswa as m', 'm.email', '=', 'p.email')
            ->where('p.email', 'like', '%@kknt.test')
            ->groupBy('m.kodept')
            ->selectRaw('m.kodept, COUNT(*) as jumlah')
            ->pluck('jumlah', 'kodept');
        $this->assertCount(12, $ketuaPerPt);
        $this->assertTrue($ketuaPerPt->every(fn ($n) => (int) $n === 3));

        // pengurangan_sampah tidak di-seed lagi (rekap dari logkegiatan)
        foreach (['capaian_kegiatan'] as $tabel) {
            $maks = DB::table($tabel)->selectRaw('COUNT(*) as n')->groupBy('email', 'bulan')->get()->max('n');
            $this->assertSame(1, (int) $maks, "$tabel: >1 data per ketua per bulan");
            $this->assertSame(0, DB::table($tabel)->whereNotIn('email', Pjdesa::select('email'))->count(), "$tabel: email bukan ketua");
        }
    }
}
