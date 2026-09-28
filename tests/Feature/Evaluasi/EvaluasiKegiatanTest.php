<?php

namespace Tests\Feature\Evaluasi;

use App\Models\Evaluasikegiatan;
use App\Models\Evaluasikegiatanjawaban;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluasiKegiatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_pertanyaan_without_false_duplicate(): void
    {
        $this->loginAs('admin');

        $this->put('admevaluasikegiatan/insert', ['pertanyaan' => '<p>Apakah program bermanfaat?</p><script>x</script>'])
            ->assertJson(['success' => true]);
        $evaluasi = Evaluasikegiatan::firstOrFail();
        $this->assertSame('<p>Apakah program bermanfaat?</p>', $evaluasi->pertanyaan);

        // Simpan ulang tanpa mengubah teks tidak boleh dianggap duplikat
        $this->put('admevaluasikegiatan/update', ['id_evaluasi' => $evaluasi->id_evaluasi, 'pertanyaan' => $evaluasi->pertanyaan])
            ->assertJson(['success' => true]);
    }

    public function test_answered_pertanyaan_cannot_be_deleted(): void
    {
        $this->loginAs('admin');
        $evaluasi = Evaluasikegiatan::factory()->create();
        Evaluasikegiatanjawaban::create(['id_evaluasi' => $evaluasi->id_evaluasi, 'jawaban' => 'Ya', 'tahun' => date('Y'), 'user' => '041234']);

        $this->put('admevaluasikegiatan/pertanyaanevaluasi/destroy', ['id_evaluasi' => $evaluasi->id_evaluasi])
            ->assertJson(['success' => false]);
    }

    public function test_pt_answers_are_saved_per_year_and_unknown_ids_ignored(): void
    {
        $pt = $this->loginAs('pt', ['email' => '041234']);
        $evaluasi = Evaluasikegiatan::factory()->create();
        Evaluasikegiatanjawaban::create(['id_evaluasi' => $evaluasi->id_evaluasi, 'jawaban' => 'Lama', 'tahun' => date('Y') - 1, 'user' => $pt->email]);

        $this->put('ptevaluasikegiatan/insert', ['jawaban' => [
            $evaluasi->id_evaluasi => 'Sangat bermanfaat',
            fake()->uuid() => 'Tidak ada pertanyaannya',
        ]])->assertJson(['success' => true]);

        $this->assertDatabaseHas('evaluasi_kegiatan_jawaban', ['user' => $pt->email, 'tahun' => date('Y'), 'jawaban' => 'Sangat bermanfaat']);
        $this->assertDatabaseHas('evaluasi_kegiatan_jawaban', ['user' => $pt->email, 'tahun' => date('Y') - 1, 'jawaban' => 'Lama']);
        $this->assertDatabaseCount('evaluasi_kegiatan_jawaban', 2);
    }

    public function test_hasil_evaluasi_listdata_shows_pt_name(): void
    {
        $this->loginAs('admin');
        $evaluasi = Evaluasikegiatan::factory()->create();
        $sp = Satuanpendidikan::factory()->create();
        Evaluasikegiatanjawaban::create(['id_evaluasi' => $evaluasi->id_evaluasi, 'jawaban' => 'Ya', 'tahun' => date('Y'), 'kodept' => $sp->npsn, 'user' => $sp->npsn]);

        $this->getJson('admevaluasikegiatan/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonFragment(['nm_lemb' => $sp->nm_lemb]);
    }
}
