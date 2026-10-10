<?php

namespace Tests\Feature;

use App\Models\PenguranganSampah;
use Database\Seeders\PenguranganSampahTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenguranganSampahTestSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_skipped_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        // Panggil langsung: db:seed di production minta konfirmasi
        $this->app->call([new PenguranganSampahTestSeeder, 'run']);

        $this->assertSame(0, PenguranganSampah::count());
    }

    public function test_seeder_creates_three_ketua_in_one_kelurahan(): void
    {
        $this->seed(PenguranganSampahTestSeeder::class);
        $this->seed(PenguranganSampahTestSeeder::class);

        $this->assertSame(3, PenguranganSampah::count());
        $this->assertSame(1, PenguranganSampah::distinct()->count('id_desa'));
    }
}
