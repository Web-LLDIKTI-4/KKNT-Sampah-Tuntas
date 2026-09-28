<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Kpi;
use App\Models\Logkegiatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LogkegiatanTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return $override + [
            'tanggal' => now()->toDateString(),
            'deskripsi' => '<p>Sosialisasi UMKM</p>',
            'volume' => 3,
            'satuan' => 'kegiatan',
            'id_kpi' => Kpi::factory()->create()->id_kpi,
            'tautan' => 'https://drive.google.com/abc',
        ];
    }

    public function test_mahasiswa_can_create_log_with_generated_uuid(): void
    {
        $user = $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload())->assertJson(['success' => true]);

        $log = Logkegiatan::where('email', $user->email)->firstOrFail();
        $this->assertTrue(Str::isUuid($log->id_log));
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload([
            'tanggal' => now()->addDay()->toDateString(),
            'volume' => 'banyak',
            'tautan' => 'javascript:alert(1)',
        ]))->assertJson(['success' => false])
            ->assertJsonValidationErrors(['tanggal', 'volume', 'tautan'], 'errors');
    }

    public function test_mahasiswa_cannot_touch_other_students_log(): void
    {
        $milikLain = Logkegiatan::factory()->create(['email' => 'lain@pps.test']);
        $this->loginAs('mahasiswa');

        $this->get('logkegiatan/edit/'.$milikLain->id_log)->assertNotFound();
        $this->put('logkegiatan/update', $this->payload(['id_log' => $milikLain->id_log]))->assertNotFound();
        $this->put('logkegiatan/destroy', ['id_log' => $milikLain->id_log])->assertNotFound();

        $this->assertDatabaseHas('logkegiatan', ['id_log' => $milikLain->id_log]);
    }

    public function test_owner_can_update_and_delete(): void
    {
        $user = $this->loginAs('mahasiswa');
        $log = Logkegiatan::factory()->create(['email' => $user->email]);

        $this->put('logkegiatan/update', $this->payload(['id_log' => $log->id_log, 'satuan' => 'orang']))
            ->assertJson(['success' => true]);
        $this->assertSame('orang', $log->fresh()->satuan);

        $this->put('logkegiatan/destroy', ['id_log' => $log->id_log])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('logkegiatan', ['id_log' => $log->id_log]);
    }

    public function test_listdata_only_shows_own_logs_and_sanitizes_output(): void
    {
        $user = $this->loginAs('mahasiswa');
        Logkegiatan::factory()->create(['email' => $user->email, 'deskripsi' => '<p onclick="x()">Milik saya</p>', 'tautan' => 'javascript:alert(1)']);
        Logkegiatan::factory()->create(['email' => 'lain@pps.test']);

        $json = $this->getJson('logkegiatan/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 1)->json('data.0.deskripsi');

        $this->assertStringContainsString('<p>Milik saya</p>', $json);
        $this->assertStringNotContainsString('javascript:', $json);
        $this->assertStringNotContainsString('onclick', $json);
    }
}
