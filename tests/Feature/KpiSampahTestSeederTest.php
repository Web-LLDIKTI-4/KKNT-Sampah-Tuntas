<?php

namespace Tests\Feature;

use App\Models\Kpisampah;
use Database\Seeders\KpiSampahTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiSampahTestSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_skipped_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        // Panggil langsung: db:seed di production minta konfirmasi
        $this->app->call([new KpiSampahTestSeeder, 'run']);

        $this->assertSame(0, Kpisampah::count());
    }

    public function test_seeder_creates_three_ketua_in_one_kelurahan(): void
    {
        $this->seed(KpiSampahTestSeeder::class);
        $this->seed(KpiSampahTestSeeder::class);

        $this->assertSame(3, Kpisampah::count());
        $this->assertSame(1, Kpisampah::distinct()->count('id_desa'));
    }
}
