<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Kehadiran;
use App\Models\Mahasiswa;
use App\Services\AttendanceService;
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

    public function test_kuliah_is_a_valid_blocking_attendance_status(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('logkehadiran/insertizin', ['status_kehadiran' => 'kuliah', 'keterangan' => 'Mengikuti perkuliahan'])
            ->assertJson(['success' => true]);
        $this->from('home')->put('logkehadiran/insert', ['mode' => 'datang'] + $this->coords)
            ->assertSessionHas('error', 'Anda sudah mengajukan kuliah hari ini.');
        $this->getJson('logkehadiran/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonPath('data.0.status_kehadiran', '<span class="badge bg-primary">Kuliah</span>');
    }

    public function test_listdata_is_scoped_to_owner(): void
    {
        $user = $this->loginAs('mahasiswa');
        Kehadiran::factory()->create(['email' => $user->email]);
        Kehadiran::factory()->create(['email' => 'lain@pps.test']);

        $this->getJson('logkehadiran/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 1);
    }

    public function test_dashboard_and_log_page_render_disabled_for_every_blocking_status(): void
    {
        $user = $this->loginAs('mahasiswa');

        foreach (AttendanceService::BLOCKING_STATUSES as $status) {
            Kehadiran::query()->delete();
            Kehadiran::factory()->create(['email' => $user->email, 'tanggal' => today(), 'status_kehadiran' => $status]);

            $home = $this->get('home')->assertOk()->getContent();
            $this->assertSame(1, preg_match('/<a[^>]*id="btnTambahLog"[^>]*>/', $home, $m), $status);
            $this->assertStringContainsString('aria-disabled="true"', $m[0], $status);
            $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*>\s*(<i[^>]*><\/i>)?\s*Datang/', $home, $status);
            $this->assertStringContainsString('role="alert"', $home, $status);

            $log = $this->get('logkegiatan')->assertOk()->getContent();
            preg_match('/<a[^>]*id="btnTambahLog"[^>]*>/', $log, $m);
            $this->assertStringContainsString('aria-disabled="true"', $m[0] ?? '', $status);
        }

        Kehadiran::query()->delete();
        $home = $this->get('home')->assertOk()->getContent();
        preg_match('/<a[^>]*id="btnTambahLog"[^>]*>/', $home, $m);
        $this->assertStringNotContainsString('aria-disabled', $m[0]);
    }

    public function test_admin_detail_shows_status_badge_including_libur_nasional(): void
    {
        $this->loginAs('admin');
        $m = Mahasiswa::factory()->create(['email' => 'hadir@pps.test']);
        Kehadiran::factory()->create(['email' => $m->email, 'tanggal' => today()->subDays(2), 'status_kehadiran' => 'kuliah']);
        Kehadiran::factory()->create(['email' => $m->email, 'tanggal' => today()->subDay(), 'status_kehadiran' => 'libur nasional']);

        $this->get('admlogkehadiran/listdata/'.rawurlencode($m->email))->assertOk()
            ->assertSee('<th class="text-center">Status</th>', false)
            ->assertSee("data: 'status_kehadiran'", false);
        $res = $this->getJson('admlogkehadiran/listdataserver/'.rawurlencode($m->email).'?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();
        $badges = collect($res->json('data'))->pluck('status_kehadiran')->all();
        $this->assertContains('<span class="badge bg-primary">Kuliah</span>', $badges);
        $this->assertContains('<span class="badge bg-dark">Libur Nasional</span>', $badges);
    }
}
