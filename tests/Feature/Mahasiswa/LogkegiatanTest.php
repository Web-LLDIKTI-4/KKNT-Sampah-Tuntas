<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\KategoriKegiatan;
use App\Models\Logkegiatan;
use App\Models\PendataanPemilahanSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Kehadiran;
use App\Services\LogHarianService;
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
            'nama_aktivitas' => 'Pembersihan selokan',
            'tautan' => 'https://example.test/bukti',
            'nama_kepala_keluarga' => 'Budi Santoso',
            'jumlah_anggota_keluarga' => 4,
            'alamat_rumah' => 'Jl. Melati No. 5',
            'rt' => '003',
            'rw' => '007',
            'memilah' => '1',
            'organik_kg' => 2.5,
            'anorganik_kg' => 1.25,
            'residu_kg' => 0,
            'mode' => 'kegiatan',
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
        $this->assertDatabaseCount('pendataan_pemilahan_sampah', 0);
    }

    public function test_daily_log_without_other_activity_only_saves_waste_data(): void
    {
        $user = $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan', 'deskripsi' => '', 'volume' => '', 'satuan' => '', 'id_kategori' => '']))
            ->assertJson(['success' => true]);

        $this->assertSame(1, PendataanPemilahanSampah::where('email', $user->email)->count());
        $this->assertDatabaseCount('logkegiatan', 0);
    }

    public function test_required_fields_and_invalid_values_are_rejected(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', [])
            ->assertJsonValidationErrors(['tanggal', 'mode'], 'errors');
        $this->put('logkegiatan/insert', ['mode' => 'kegiatan'])
            ->assertJsonValidationErrors(['deskripsi', 'volume', 'satuan', 'id_kategori'], 'errors');
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

    public function test_insert_cannot_bypass_unique_description_with_existing_id_log(): void
    {
        $user = $this->loginAs('mahasiswa');
        $existing = Logkegiatan::factory()->create([
            'email' => $user->email,
            'tanggal' => today()->toDateString(),
            'deskripsi' => '<p>Membersihkan lingkungan</p>',
        ]);

        $this->put('logkegiatan/insert', $this->payload(['id_log' => $existing->id_log]))
            ->assertJsonValidationErrors('deskripsi', 'errors');
        $this->assertDatabaseCount('logkegiatan', 1);
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
        $this->put('logkegiatan/update', $this->payload(['id_log' => $other->id_log, 'tanggal' => now()->subDay()->toDateString()]))->assertNotFound();
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
        Logkegiatan::forceCreate(['email' => $user->email, 'tanggal' => today(), 'nama_kepala_keluarga' => 'Pindahan']);
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
            ->assertSee('Kategori Kegiatan')
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
        $this->assertDatabaseCount('pendataan_pemilahan_sampah', 0);

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

    public function test_rt_rw_must_be_exactly_three_digits_on_daily_log_insert(): void
    {
        $user = $this->loginAs('mahasiswa');

        foreach (['1', '12', '0001', 'ab1', '01a'] as $i => $invalid) {
            $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan', 'rt' => $invalid, 'rw' => $invalid]))
                ->assertJsonValidationErrors([
                    'rt' => 'RT harus 3 digit angka, contoh 001.',
                    'rw' => 'RW harus 3 digit angka, contoh 012.',
                ], 'errors');
        }
        $this->assertDatabaseCount('pendataan_pemilahan_sampah', 0);

        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan', 'rt' => '001', 'rw' => '012']))
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('pendataan_pemilahan_sampah', ['email' => $user->email, 'rt' => '001', 'rw' => '012']);
    }

    public function test_multiple_daily_logs_on_same_date_are_all_saved(): void
    {
        $user = $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan']))->assertJson(['success' => true]);
        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan', 'nama_kepala_keluarga' => 'Rumah Lain']))
            ->assertJson(['success' => true])->assertJsonMissingPath('errors');
        $this->put('logkegiatan/insert', $this->payload(['mode' => 'kegiatan', 'deskripsi' => '<p>Kegiatan lain</p>']))
            ->assertJson(['success' => true])->assertJsonMissingPath('errors');

        $this->assertSame(2, PendataanPemilahanSampah::where('email', $user->email)->whereDate('tanggal', today())->count());
        $this->assertSame(1, Logkegiatan::where('email', $user->email)->whereDate('tanggal', today())->count());
    }

    public function test_second_daily_log_with_other_activity_on_same_date_is_saved(): void
    {
        $user = $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload())->assertJson(['success' => true]);
        $this->put('logkegiatan/insert', $this->payload(['deskripsi' => '<p>Kegiatan lain</p>']))->assertJson(['success' => true]);
        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan']))->assertJson(['success' => true]);

        $this->assertSame(1, PendataanPemilahanSampah::where('email', $user->email)->count());
        $this->assertSame(2, Logkegiatan::where('email', $user->email)->count());
    }

    public function test_existing_activity_log_does_not_block_daily_log_on_same_date(): void
    {
        $user = $this->loginAs('mahasiswa');
        Logkegiatan::factory()->create(['email' => $user->email, 'tanggal' => today()->toDateString()]);

        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan']))->assertJson(['success' => true]);
        $this->assertSame(1, PendataanPemilahanSampah::where('email', $user->email)->count());
    }

    public function test_daily_log_on_different_date_or_by_other_student_is_allowed(): void
    {
        $user = $this->loginAs('mahasiswa');
        PendataanPemilahanSampah::factory()->create(['email' => 'lain@pps.test', 'tanggal' => today()->toDateString()]);
        Logkegiatan::factory()->create(['email' => 'lain@pps.test', 'tanggal' => today()->toDateString()]);

        $this->put('logkegiatan/insert', $this->payload())->assertJson(['success' => true]);
        $this->put('logkegiatan/insert', $this->payload(['tanggal' => today()->subDay()->toDateString(), 'deskripsi' => '<p>Kemarin</p>']))
            ->assertJson(['success' => true]);
        $this->put('logkegiatan/insert', $this->payload(['tanggal' => today()->subDays(2)->toDateString(), 'mode' => 'pendataan']))
            ->assertJson(['success' => true]);

        $this->assertSame(1, PendataanPemilahanSampah::where('email', $user->email)->count());
        $this->assertSame(2, Logkegiatan::where('email', $user->email)->count());
    }

    public function test_datetime_formatted_date_is_normalized_and_saved_on_same_date(): void
    {
        $user = $this->loginAs('mahasiswa');
        $yesterday = today()->subDay();
        $this->put('logkegiatan/insert', $this->payload(['tanggal' => $yesterday->toDateString()]))->assertJson(['success' => true]);

        foreach ([' 10:30:00', 'T10:30:00', ' 00:00:01'] as $suffix) {
            $this->putJson('logkegiatan/insert', $this->payload([
                'tanggal' => $yesterday->toDateString().$suffix,
                'deskripsi' => '<p>Lain'.$suffix.'</p>',
            ]), $this->ajax)->assertOk()->assertJson(['success' => true]);
        }

        $this->assertDatabaseCount('pendataan_pemilahan_sampah', 0);
        $this->assertSame(4, Logkegiatan::where('email', $user->email)->whereDate('tanggal', $yesterday)->count());
        $this->assertSame([$yesterday->toDateString()], Logkegiatan::where('email', $user->email)->pluck('tanggal')
            ->map(fn ($t) => substr((string) $t, 0, 10))->unique()->values()->all());
    }

    public function test_datetime_date_still_cannot_bypass_unique_description_per_day(): void
    {
        $user = $this->loginAs('mahasiswa');
        $yesterday = today()->subDay()->toDateString();
        $this->put('logkegiatan/insert', $this->payload(['tanggal' => $yesterday]))->assertJson(['success' => true]);

        $this->put('logkegiatan/insert', $this->payload(['tanggal' => $yesterday.' 10:30:00']))
            ->assertJsonValidationErrors(['deskripsi'], 'errors');
        $this->assertSame(1, Logkegiatan::where('email', $user->email)->count());
    }

    public function test_edit_own_activity_log_to_already_filled_date_is_allowed(): void
    {
        $user = $this->loginAs('mahasiswa');
        $own = Logkegiatan::factory()->create(['email' => $user->email, 'tanggal' => today()->subDay()->toDateString()]);
        $filled = Logkegiatan::factory()->create(['email' => $user->email, 'tanggal' => today()->subDays(2)->toDateString()]);

        $this->put('logkegiatan/update', $this->payload(['id_log' => $own->id_log, 'tanggal' => $own->tanggal, 'deskripsi' => '<p>Diperbarui</p>']))
            ->assertJson(['success' => true]);
        $this->assertSame('<p>Diperbarui</p>', $own->fresh()->deskripsi);

        $this->put('logkegiatan/update', $this->payload(['id_log' => $own->id_log, 'tanggal' => $filled->tanggal.' 10:30:00', 'deskripsi' => '<p>Pindah</p>']))
            ->assertJson(['success' => true])->assertJsonMissingPath('errors');
        $this->assertSame(2, Logkegiatan::where('email', $user->email)->whereDate('tanggal', $filled->tanggal)->count());
    }

    public function test_update_with_array_id_log_returns_validation_error_not_500(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('logkegiatan/update', $this->payload(['id_log' => [(string) Str::uuid()]]))
            ->assertOk()->assertJsonValidationErrors(['id_log'], 'errors');
    }

    public function test_mode_is_required_and_must_be_valid(): void
    {
        $this->loginAs('mahasiswa');
        $required = 'Pilih jenis isian: pendataan sampah atau log harian.';

        $this->put('logkegiatan/insert', Arr::except($this->payload(), 'mode'))
            ->assertJsonValidationErrors(['mode' => $required], 'errors');
        $this->put('logkegiatan/insert', $this->payload(['mode' => '']))
            ->assertJsonValidationErrors(['mode' => $required], 'errors');
        foreach (['keduanya', 'PENDATAAN', ['pendataan']] as $invalid) {
            $this->put('logkegiatan/insert', $this->payload(['mode' => $invalid]))
                ->assertOk()->assertJsonValidationErrors(['mode' => 'Pilihan tidak valid.'], 'errors');
        }

        $this->assertDatabaseCount('pendataan_pemilahan_sampah', 0);
        $this->assertDatabaseCount('logkegiatan', 0);
    }

    public function test_tanggal_is_required_in_both_modes(): void
    {
        $this->loginAs('mahasiswa');

        foreach (['pendataan', 'kegiatan'] as $mode) {
            $this->put('logkegiatan/insert', $this->payload(['mode' => $mode, 'tanggal' => '']))
                ->assertJsonValidationErrors(['tanggal'], 'errors');
            $this->put('logkegiatan/insert', Arr::except($this->payload(['mode' => $mode]), 'tanggal'))
                ->assertJsonValidationErrors(['tanggal'], 'errors');
        }

        $this->assertDatabaseCount('pendataan_pemilahan_sampah', 0);
        $this->assertDatabaseCount('logkegiatan', 0);
    }

    public function test_fields_of_other_mode_are_ignored_and_not_saved(): void
    {
        $user = $this->loginAs('mahasiswa');

        // Field kegiatan (bahkan invalid) diabaikan saat mode=pendataan
        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan', 'volume' => -1, 'tautan' => 'javascript:alert(1)', 'id_kategori' => 'tidak-ada']))
            ->assertJson(['success' => true])->assertJsonMissingPath('errors');
        $this->assertDatabaseCount('logkegiatan', 0);
        $this->assertDatabaseHas('pendataan_pemilahan_sampah', ['email' => $user->email, 'nama_kepala_keluarga' => 'Budi Santoso', 'jumlah_anggota_keluarga' => 4]);

        // Field pendataan (bahkan invalid) diabaikan saat mode=kegiatan
        $this->put('logkegiatan/insert', $this->payload(['rt' => 'x', 'memilah' => 'mungkin', 'organik_kg' => -1, 'jumlah_anggota_keluarga' => 0]))
            ->assertJson(['success' => true])->assertJsonMissingPath('errors');
        $this->assertSame(1, PendataanPemilahanSampah::where('email', $user->email)->count());
        $log = Logkegiatan::where('email', $user->email)->firstOrFail();
        $this->assertNull($log->nama_kepala_keluarga);
        $this->assertNull($log->rt);
        $this->assertNull($log->organik_kg);
    }

    public function test_log_limit_is_5_per_30_minutes_combined_across_both_tables(): void
    {
        $user = $this->loginAs('mahasiswa');
        $this->freezeSecond();
        $start = now()->copy();
        $limitMsg = fn (int $menit) => 'Batas input tercapai (maksimal 5 isian per 30 menit). Coba lagi sekitar '.$menit.' menit lagi.';

        // Dibuat di luar window tidak dihitung
        PendataanPemilahanSampah::factory()->count(5)->create(['email' => $user->email, 'created_at' => $start->copy()->subMinutes(31)]);
        Logkegiatan::factory()->count(5)->create(['email' => $user->email, 'created_at' => $start->copy()->subMinutes(31)]);

        // 3 pendataan (tanggal mundur tetap dihitung dari created_at) + 2 kegiatan
        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan', 'tanggal' => today()->subDays(2)->toDateString()]))->assertJson(['success' => true]);
        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan']))->assertJson(['success' => true]);
        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan']))->assertJson(['success' => true]);
        $this->put('logkegiatan/insert', $this->payload(['deskripsi' => '<p>Ke-4</p>']))->assertJson(['success' => true]);
        $this->put('logkegiatan/insert', $this->payload(['deskripsi' => '<p>Ke-5</p>']))->assertJson(['success' => true]);

        // Isian ke-6 ditolak di kedua mode
        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan']))
            ->assertJsonValidationErrors(['mode' => $limitMsg(30)], 'errors');
        $this->put('logkegiatan/insert', $this->payload(['deskripsi' => '<p>Ke-6</p>']))
            ->assertJsonValidationErrors(['mode' => $limitMsg(30)], 'errors');
        $this->assertSame(8, PendataanPemilahanSampah::where('email', $user->email)->count());
        $this->assertSame(7, Logkegiatan::where('email', $user->email)->count());

        $this->travelTo($start->copy()->addMinutes(10));
        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan']))
            ->assertJsonValidationErrors(['mode' => $limitMsg(20)], 'errors');

        // Setelah 30 menit boleh input lagi
        $this->travelTo($start->copy()->addMinutes(30));
        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan']))
            ->assertJson(['success' => true])->assertJsonMissingPath('errors');
    }

    public function test_log_limit_window_is_rolling(): void
    {
        $user = $this->loginAs('mahasiswa');
        $this->freezeSecond();
        $start = now()->copy();
        $limitMsg = fn (int $menit) => 'Batas input tercapai (maksimal 5 isian per 30 menit). Coba lagi sekitar '.$menit.' menit lagi.';

        PendataanPemilahanSampah::factory()->create(['email' => $user->email, 'created_at' => $start->copy()->subMinutes(20)]);
        Logkegiatan::factory()->count(2)->create(['email' => $user->email]);
        PendataanPemilahanSampah::factory()->count(2)->create(['email' => $user->email]);

        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan']))
            ->assertJsonValidationErrors(['mode' => $limitMsg(10)], 'errors');

        // Isian tertua keluar dari window -> satu slot terbuka, lalu penuh lagi
        $this->travelTo($start->copy()->addMinutes(11));
        $this->put('logkegiatan/insert', $this->payload())->assertJson(['success' => true])->assertJsonMissingPath('errors');
        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan']))
            ->assertJsonValidationErrors(['mode' => $limitMsg(19)], 'errors');
        $this->assertSame(3, Logkegiatan::where('email', $user->email)->count());
    }

    public function test_service_store_rechecks_limit_and_throws_on_mode_when_window_full(): void
    {
        $user = $this->loginAs('mahasiswa');
        $this->freezeSecond();
        PendataanPemilahanSampah::factory()->count(3)->create(['email' => $user->email]);
        Logkegiatan::factory()->count(2)->create(['email' => $user->email]);

        // Bypass FormRequest: service tetap menolak (anti race)
        foreach (['pendataan', 'kegiatan'] as $mode) {
            try {
                app(LogHarianService::class)->store($this->payload(['mode' => $mode]), $user->email);
                $this->fail('ValidationException tidak dilempar untuk mode '.$mode);
            } catch (ValidationException $e) {
                $this->assertSame(['mode' => ['Batas input tercapai (maksimal 5 isian per 30 menit). Coba lagi sekitar 30 menit lagi.']], $e->errors());
            }
        }

        $this->assertSame(3, PendataanPemilahanSampah::where('email', $user->email)->count());
        $this->assertSame(2, Logkegiatan::where('email', $user->email)->count());
    }

    public function test_log_limit_of_other_student_does_not_affect_user(): void
    {
        PendataanPemilahanSampah::factory()->count(LogHarianService::MAX_LOG_PER_WINDOW)->create(['email' => 'lain@pps.test']);
        Logkegiatan::factory()->count(LogHarianService::MAX_LOG_PER_WINDOW)->create(['email' => 'lain@pps.test']);
        $user = $this->loginAs('mahasiswa');

        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan']))->assertJson(['success' => true]);
        $this->put('logkegiatan/insert', $this->payload())->assertJson(['success' => true]);
        $this->assertSame(1, PendataanPemilahanSampah::where('email', $user->email)->count());
        $this->assertSame(1, Logkegiatan::where('email', $user->email)->count());
    }

    public function test_daily_log_weight_max_is_inclusive_on_insert(): void
    {
        $user = $this->loginAs('mahasiswa');
        $max = LogHarianService::MAX_BERAT_KG;

        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan', 'organik_kg' => $max, 'anorganik_kg' => $max, 'residu_kg' => $max]))
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('pendataan_pemilahan_sampah', ['email' => $user->email, 'organik_kg' => $max, 'anorganik_kg' => $max, 'residu_kg' => $max]);

        $this->put('logkegiatan/insert', $this->payload(['mode' => 'pendataan', 'organik_kg' => '1000.01', 'anorganik_kg' => '1000.01', 'residu_kg' => '1000.01']))
            ->assertJsonValidationErrors([
                'organik_kg' => 'Berat maksimal '.$max.' kg.',
                'anorganik_kg' => 'Berat maksimal '.$max.' kg.',
                'residu_kg' => 'Berat maksimal '.$max.' kg.',
            ], 'errors');
        $this->assertSame(1, PendataanPemilahanSampah::where('email', $user->email)->count());
    }

    public function test_daily_log_update_is_throttled_at_30_per_minute(): void
    {
        $user = $this->loginAs('mahasiswa');
        $own = Logkegiatan::factory()->create(['email' => $user->email, 'tanggal' => today()->subDay()->toDateString()]);

        for ($i = 1; $i <= 30; $i++) {
            $this->put('logkegiatan/update', $this->payload(['id_log' => $own->id_log, 'tanggal' => $own->tanggal, 'deskripsi' => '<p>Ubah '.$i.'</p>']))
                ->assertOk();
        }
        $this->put('logkegiatan/update', $this->payload(['id_log' => $own->id_log, 'tanggal' => $own->tanggal, 'deskripsi' => '<p>Ubah 31</p>']))
            ->assertStatus(429);
        $this->assertSame('<p>Ubah 30</p>', $own->fresh()->deskripsi);
    }
}
