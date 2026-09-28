<?php

namespace Tests\Feature\Admin;

use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PerguruanTinggiSaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_upserts_only_known_columns(): void
    {
        $this->loginAs('admin');
        $idSp = (string) Str::uuid();
        Http::fake(['*/splldikti4/*' => Http::response([
            ['id_sp' => $idSp, 'npsn' => '041001', 'nm_lemb' => 'Universitas Uji', 'kolom_asing' => 'x'],
            ['npsn' => '041002'],
        ])]);

        $this->put('perguruantinggi/getdata')
            ->assertJson(['success' => true, 'messages' => '0 Data berhasil di update dan 1 Data berhasil disimpan!']);
        $this->assertDatabaseHas('ref_satuanpendidikan', ['id_sp' => $idSp, 'nm_lemb' => 'Universitas Uji']);
    }

    public function test_sync_reports_connection_error(): void
    {
        $this->loginAs('admin');
        Http::fake(['*' => Http::response('error', 500)]);

        $this->put('perguruantinggi/getdata')->assertJson(['success' => false, 'messages' => 'Koneksi ke PDDIKTI error!']);
    }

    public function test_insert_validates_kodept_format(): void
    {
        $this->loginAs('admin');
        Http::fake();

        $this->put('perguruantinggi/insert', ['kodept' => '12; DROP'])->assertJsonValidationErrors('kodept', 'errors');
        Http::assertNothingSent();
    }

    public function test_public_saran_is_validated_and_throttled(): void
    {
        $this->put('saran/insert', ['nama' => 'A', 'email' => 'bukan-email', 'saran' => 'x'])
            ->assertJsonValidationErrors('email', 'errors');
        $this->put('saran/insert', ['nama' => 'A', 'email' => 'a@pps.test', 'saran' => 'Bagus'])->assertJson(['success' => true]);
        $this->put('saran/insert', ['nama' => 'A', 'email' => 'a@pps.test', 'saran' => 'Lagi'])
            ->assertJsonPath('errors.email.0', 'Pesan anda sudah ada!');

        foreach (range(1, 3) as $i) {
            $this->put('saran/insert', ['nama' => 'A', 'email' => "x$i@pps.test", 'saran' => 'x']);
        }
        $this->put('saran/insert', ['nama' => 'A', 'email' => 'z@pps.test', 'saran' => 'x'])->assertStatus(429);
    }

    public function test_public_peserta_counts_per_pt(): void
    {
        $sp = Satuanpendidikan::factory()->create();
        Mahasiswa::factory()->count(2)->create(['kodept' => $sp->npsn]);

        $this->getJson('ptpeserta/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('data.0.jumlah_mhs', 2)->assertJsonPath('data.0.nm_lemb', $sp->nm_lemb);
    }
}
