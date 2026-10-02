<?php

namespace Tests\Feature;

use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpisampah;
use App\Models\Pjdesa;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\KpiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KpiSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_kpi_dummy_data_and_is_rerunnable(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, Kpi::count());
        $this->assertSame(12, Pjdesa::where('email', 'like', '%@kknt.test')->count());
        $this->assertTrue(Kpicapaian::where('status_capaian', 'Y')->exists());
        $this->assertSame(12 * 3 - 1, Kpisampah::count());

        // Dijalankan ulang tidak menggandakan data maupun menambah ketua
        $jumlah = Kpicapaian::count();
        $this->seed(KpiSeeder::class);
        $this->assertSame($jumlah, Kpicapaian::count());
        $this->assertSame(3, Kpi::count());
        $this->assertSame(12, Pjdesa::count());

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
        $this->assertSame(12, Kpisampah::distinct()->count('id_desa'));
    }
}
