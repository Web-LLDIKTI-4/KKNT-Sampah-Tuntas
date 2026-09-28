<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Logbulanan;
use App\Models\Tugasakhir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogbulananTugasakhirTest extends TestCase
{
    use RefreshDatabase;

    private function deskripsi(int $words): string
    {
        return '<p>'.trim(str_repeat('kata ', $words)).'</p><script>alert(1)</script>';
    }

    public function test_log_bulanan_requires_200_words_and_is_upserted_per_month(): void
    {
        $user = $this->loginAs('mahasiswa');
        $base = ['bulan' => 5, 'tahun' => date('Y'), 'tautan' => 'https://drive.google.com/x'];

        $this->put('logbulanan/insert', $base + ['deskripsi' => $this->deskripsi(50)])
            ->assertJsonPath('errors.deskripsi.0', 'Deskripsi minimal 200 kata!');

        $this->put('logbulanan/insert', $base + ['deskripsi' => $this->deskripsi(200)])->assertJson(['success' => true]);
        $this->put('logbulanan/insert', $base + ['deskripsi' => $this->deskripsi(210)])->assertJson(['success' => true]);

        $logs = Logbulanan::ownedBy($user)->get();
        $this->assertCount(1, $logs);
        $this->assertStringNotContainsString('<script>', $logs->first()->deskripsi);
    }

    public function test_log_bulanan_rejects_invalid_period(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('logbulanan/insert', ['bulan' => 13, 'tahun' => 'abc', 'deskripsi' => $this->deskripsi(200)])
            ->assertJsonValidationErrors(['bulan', 'tahun'], 'errors');
    }

    public function test_cannot_delete_other_students_log_bulanan(): void
    {
        $lain = Logbulanan::factory()->create(['email' => 'lain@pps.test']);
        $this->loginAs('mahasiswa');

        $this->put('logbulanan/destroy', ['id_logbulanan' => $lain->id_logbulanan])->assertJson(['success' => false]);
        $this->assertDatabaseHas('logkegiatan_bulanan', ['id_logbulanan' => $lain->id_logbulanan]);
    }

    public function test_tugas_akhir_single_entry_and_ownership(): void
    {
        $user = $this->loginAs('mahasiswa');

        $this->put('tugasakhir/insert', ['tautan' => 'bukan-url'])->assertJsonValidationErrors('tautan', 'errors');
        $this->put('tugasakhir/insert', ['tautan' => 'https://doc.test/ta'])->assertJson(['success' => true]);
        $this->put('tugasakhir/insert', ['tautan' => 'https://doc.test/ta2'])->assertJsonPath('errors.tautan.0', 'Tugas akhir sudah ada!');

        $milik = Tugasakhir::ownedBy($user)->firstOrFail();
        $this->put('tugasakhir/update', ['id_tugasakhir' => $milik->id_tugasakhir, 'tautan' => 'https://doc.test/revisi'])
            ->assertJson(['success' => true]);

        $lain = Tugasakhir::factory()->create(['email' => 'lain@pps.test']);
        $this->get('tugasakhir/edit/'.$lain->id_tugasakhir)->assertNotFound();
        $this->put('tugasakhir/update', ['id_tugasakhir' => $lain->id_tugasakhir, 'tautan' => 'https://x.test'])->assertNotFound();
        $this->put('tugasakhir/destroy', ['id_tugasakhir' => $lain->id_tugasakhir])->assertJson(['success' => false]);
    }
}
