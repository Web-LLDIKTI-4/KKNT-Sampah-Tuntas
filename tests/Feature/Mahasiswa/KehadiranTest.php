<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Kehadiran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KehadiranTest extends TestCase
{
    use RefreshDatabase;

    private array $coords = ['latitude_datang' => -6.8992, 'longitude_datang' => 107.6377, 'latitude_pulang' => -6.8992, 'longitude_pulang' => 107.6377];

    public function test_datang_then_pulang_flow(): void
    {
        $user = $this->loginAs('mahasiswa');

        $this->from('home')->put('logkehadiran/insert', ['mode' => 'pulang'] + $this->coords)
            ->assertSessionHas('error', 'Silakan absen datang terlebih dahulu.');

        $this->from('home')->put('logkehadiran/insert', ['mode' => 'datang'] + $this->coords)->assertSessionHas('success');
        $this->from('home')->put('logkehadiran/insert', ['mode' => 'datang'] + $this->coords)
            ->assertSessionHas('error', 'Anda sudah melakukan absen datang hari ini.');

        $this->from('home')->put('logkehadiran/insert', ['mode' => 'pulang'] + $this->coords)->assertSessionHas('success');

        $today = Kehadiran::ownedBy($user)->firstOrFail();
        $this->assertNotNull($today->waktu_masuk);
        $this->assertNotNull($today->waktu_pulang);
        $this->assertSame('hadir', $today->status_kehadiran);
    }

    public function test_coordinates_are_required(): void
    {
        $this->loginAs('mahasiswa');

        $this->from('home')->put('logkehadiran/insert', ['mode' => 'datang'])
            ->assertSessionHasErrors(['latitude_datang', 'longitude_datang']);
        $this->assertDatabaseCount('kehadiran', 0);
    }

    public function test_radius_is_enforced_only_when_enabled(): void
    {
        $this->loginAs('mahasiswa');
        config(['attendance.enforce_radius' => true, 'attendance.radius_meter' => 100]);

        $this->from('home')->put('logkehadiran/insert', ['mode' => 'datang', 'latitude_datang' => -7.5, 'longitude_datang' => 110.0])
            ->assertSessionHas('error', 'Lokasi Anda di luar radius absensi yang diizinkan.');
    }

    public function test_izin_blocks_attendance_and_validates_status(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('logkehadiran/insertizin', ['status_kehadiran' => 'libur', 'keterangan' => 'x'])
            ->assertJsonValidationErrors('status_kehadiran', 'errors');
        $this->put('logkehadiran/insertizin', ['status_kehadiran' => 'sakit', 'keterangan' => 'Demam'])
            ->assertJson(['success' => true]);
        $this->put('logkehadiran/insertizin', ['status_kehadiran' => 'izin', 'keterangan' => 'Lagi'])
            ->assertJsonPath('errors.tanggal.0', 'Data pada tanggal tersebut terisi!');

        $this->from('home')->put('logkehadiran/insert', ['mode' => 'datang'] + $this->coords)
            ->assertSessionHas('error', 'Anda sudah mengajukan sakit hari ini.');
    }

    public function test_listdata_is_scoped_to_owner(): void
    {
        $user = $this->loginAs('mahasiswa');
        Kehadiran::factory()->create(['email' => $user->email]);
        Kehadiran::factory()->create(['email' => 'lain@pps.test']);

        $this->getJson('logkehadiran/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 1);
    }
}
