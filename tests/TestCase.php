<?php

namespace Tests;

use App\Models\Desa;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // .env lokal bisa belum punya APP_KEY; pakai key sementara khusus test
        if (empty(config('app.key'))) {
            config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        }
    }

    protected function loginAs(string $role, array $attributes = []): User
    {
        $lokasi = LokasiProgram::factory()->create();
        $user = User::factory()->role($role)->create($attributes + ['location_program' => $lokasi->id]);

        if ($role === 'mahasiswa') {
            $mahasiswa = Mahasiswa::factory()->create([
                'email' => $user->email,
                'location_program' => $lokasi->id,
            ]);
            Mahasiswa_lokasi::create([
                'tahun' => (int) date('Y'),
                'id_mahasiswa' => $mahasiswa->id_mahasiswa,
                'id_desa' => Desa::factory()->create()->id_desa,
                'user_in_up' => $user->email,
            ]);
        }

        $this->actingAs($user);

        return $user;
    }
}
