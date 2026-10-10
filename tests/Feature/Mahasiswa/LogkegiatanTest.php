<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\KategoriKegiatan;
use App\Models\Logkegiatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Models\Kehadiran;
use Tests\TestCase;

class LogkegiatanTest extends TestCase
{
    use RefreshDatabase;

    private array $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

    private function payload(array $override = []): array
    {
        return $override + [
            'tanggal' => now()->toDateString(),
            'deskripsi' => '<p>Membersihkan lingkungan</p>',
            'volume' => '2',
            'satuan' => 'kegiatan',
            'id_kategori' => KategoriKegiatan::factory()->create()->id_kategori,
            'tautan' => 'https://example.test/bukti',
        ];
    }

    public function test_mahasiswa_can_create_daily_activity_log(): void
    {
        $user = $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload())->assertJson(['success' => true]);

        $log = Logkegiatan::where('email', $user->email)->firstOrFail();
        $this->assertTrue(Str::isUuid($log->id_log));
        $this->assertSame('<p>Membersihkan lingkungan</p>', $log->deskripsi);
        $this->assertSame('2', (string) $log->volume);
        $this->assertSame('kegiatan', $log->satuan);
        $this->assertSame('https://example.test/bukti', $log->tautan);
        $this->assertNull($log->nama_kepala_keluarga);
    }

    public function test_required_fields_and_invalid_values_are_rejected(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', [])
            ->assertJsonValidationErrors(['tanggal', 'deskripsi', 'volume', 'satuan', 'id_kategori'], 'errors');
        $this->put('logkegiatan/insert', $this->payload([
            'tanggal' => now()->addDay()->toDateString(),
            'volume' => -1,
            'tautan' => 'javascript:alert(1)',
        ]))->assertJsonValidationErrors(['tanggal', 'volume', 'tautan'], 'errors');
    }

    public function test_daily_log_is_unique_by_description_per_day_and_owner(): void
    {
        $this->loginAs('mahasiswa');
        $payload = $this->payload();

        $this->put('logkegiatan/insert', $payload)->assertJson(['success' => true]);
        $this->put('logkegiatan/insert', $payload)->assertJsonValidationErrors('deskripsi', 'errors');
    }

    public function test_student_can_only_update_or_delete_own_daily_logs(): void
    {
        $user = $this->loginAs('mahasiswa');
        $own = Logkegiatan::factory()->create(['email' => $user->email]);
        $other = Logkegiatan::factory()->create(['email' => 'lain@pps.test']);

        $this->get('logkegiatan/edit/'.$own->id_log)->assertOk()->assertSee('name="deskripsi"', false);
        $this->put('logkegiatan/update', $this->payload(['id_log' => $own->id_log, 'deskripsi' => 'Kegiatan diperbarui']))
            ->assertJson(['success' => true]);
        $this->assertSame('Kegiatan diperbarui', $own->fresh()->deskripsi);

        $this->get('logkegiatan/edit/'.$other->id_log)->assertNotFound();
        $this->put('logkegiatan/update', $this->payload(['id_log' => $other->id_log]))->assertNotFound();
        $this->put('logkegiatan/destroy', ['id_log' => $other->id_log])->assertNotFound();
        $this->put('logkegiatan/destroy', ['id_log' => $own->id_log])->assertJson(['success' => true]);
    }

    public function test_daily_log_list_only_shows_daily_activities(): void
    {
        $user = $this->loginAs('mahasiswa');
        $kategori = KategoriKegiatan::factory()->create(['nama_kategori' => 'Kebersihan Lingkungan']);
        Logkegiatan::factory()->create([
            'email' => $user->email,
            'id_kategori' => $kategori->id_kategori,
            'deskripsi' => '<p>Membersihkan lingkungan</p>',
            'volume' => '2',
            'satuan' => 'kegiatan',
            'tautan' => 'https://example.test/bukti',
        ]);
        Logkegiatan::create(['email' => $user->email, 'tanggal' => today(), 'nama_kepala_keluarga' => 'Pindahan']);
        Logkegiatan::factory()->create(['email' => 'lain@pps.test']);

        $this->get('logkegiatan/tambah')
            ->assertOk()
            ->assertSee('name="tanggal"', false)
            ->assertSee('name="deskripsi"', false)
            ->assertSee('name="volume"', false)
            ->assertSee('name="satuan"', false)
            ->assertSee('name="id_kategori"', false)
            ->assertSee('name="id_kategori" class="form-select" required', false)
            ->assertDontSee('Pilih Kategori Kegiatan (opsional)')
            ->assertSee('name="tautan"', false);
        $this->get('logkegiatan/listdata')
            ->assertOk()
            ->assertSee('Deskripsi Kegiatan')
            ->assertSee('Volume')
            ->assertSee('Satuan')
            ->assertSee('Aktivitas')
            ->assertSee('Tautan Bukti')
            ->assertDontSee('Nama Kepala Keluarga');

        $this->getJson('logkegiatan/listdataserver?draw=1&start=0&length=10', $this->ajax)
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.nama_kategori', 'Kebersihan Lingkungan')
            ->assertJsonPath('data.0.volume', '2')
            ->assertJsonPath('data.0.satuan', 'kegiatan')
            ->assertJsonPath('data.0.tautan', '<a href="https://example.test/bukti" target="_blank" rel="noopener noreferrer">Lihat bukti</a>');
    }

    public function test_non_mahasiswa_cannot_create_daily_log(): void
    {
        $this->loginAs('dpl');

        $this->put('logkegiatan/insert', $this->payload())->assertRedirect();
        $this->assertDatabaseCount('logkegiatan', 0);
    }

    public function test_pt_cannot_create_daily_log(): void
    {
        $pt = $this->loginAs('pt');

        $this->put('logkegiatan/insert', $this->payload())->assertRedirect();
        $this->get('logkegiatan')->assertRedirect();
        $this->assertDatabaseMissing('logkegiatan', ['email' => $pt->email]);
    }

    public function test_blocking_attendance_status_rejects_new_daily_log_but_allows_update(): void
    {
        $user = $this->loginAs('mahasiswa');
        $log = Logkegiatan::factory()->create(['email' => $user->email, 'tanggal' => today()->subDay()->toDateString()]);
        $this->put('logkehadiran/insertizin', ['status_kehadiran' => 'kuliah', 'keterangan' => 'Kuliah'])
            ->assertJson(['success' => true]);

        $this->put('logkegiatan/insert', $this->payload())->assertJsonValidationErrors(['tanggal'], 'errors');
        $this->assertDatabaseCount('logkegiatan', 1);

        $this->put('logkegiatan/update', $this->payload([
            'id_log' => $log->id_log,
            'tanggal' => $log->tanggal,
            'deskripsi' => '<p>Diperbarui</p>',
        ]))->assertJson(['success' => true]);
    }

    public function test_add_button_disabled_when_status_is_blocking(): void
    {
        $user = $this->loginAs('mahasiswa');
        Kehadiran::factory()->create(['email' => $user->email, 'tanggal' => today(), 'status_kehadiran' => 'kuliah']);

        $html = $this->get('logkegiatan')->assertOk()->getContent();
        $this->assertSame(1, preg_match('/<a[^>]*id="btnTambahLog"[^>]*>/', $html, $m));
        $this->assertStringContainsString('aria-disabled="true"', $m[0]);
    }
}
