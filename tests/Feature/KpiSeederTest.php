<?php

namespace Tests\Feature;

use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpitarget;
use App\Models\Pjdesa;
use App\Services\KpiRekapService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\KpiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_kpi_dummy_data_and_is_rerunnable(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, Kpi::count());
        $this->assertSame(6, Kpitarget::count());
        $this->assertSame(13, Pjdesa::where('email', 'like', '%@kknt.test')->count());
        $this->assertTrue(Kpicapaian::where('status_capaian', 'Y')->exists());

        $rekap = app(KpiRekapService::class);
        $this->assertSame(3, $rekap->rekapPerPt([])->pluck('kodept')->unique()->count());
        $this->assertTrue($rekap->rekapPerKpi([])->every(fn ($r) => $r->capaian === null || $r->capaian <= 100));

        // Dijalankan ulang tidak menggandakan data
        $jumlah = Kpicapaian::count();
        $this->seed(KpiSeeder::class);
        $this->assertSame($jumlah, Kpicapaian::count());
        $this->assertSame(6, Kpitarget::count());
    }
}
