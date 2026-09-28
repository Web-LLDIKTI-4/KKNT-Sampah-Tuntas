<?php

namespace Tests\Feature\Admin;

use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\Kpicapaian;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\Nilaikonversi;
use App\Models\Tugasakhir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PersonMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_mahasiswa_removes_all_related_data(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create();
        User::factory()->role('mahasiswa')->create(['email' => $mhs->email]);
        Logkegiatan::factory()->create(['email' => $mhs->email]);
        Kpicapaian::factory()->create(['email' => $mhs->email]);
        Nilaikonversi::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa]);
        Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => 'dpl@pps.test']);

        $this->put('mahasiswa/destroy', ['id_mahasiswa' => $mhs->id_mahasiswa])->assertJson(['success' => true]);

        foreach (['mahasiswa' => 'email', 'logkegiatan' => 'email', 'kpi_capaian' => 'email', 'users' => 'email'] as $table => $col) {
            $this->assertDatabaseMissing($table, [$col => $mhs->email]);
        }
        $this->assertDatabaseMissing('nilai_konversi', ['id_mahasiswa' => $mhs->id_mahasiswa]);
        $this->assertDatabaseMissing('dpl_mentoring', ['email_mahasiswa' => $mhs->email]);
    }

    public function test_deleting_dpl_keeps_student_final_assignment(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create();
        $ta = Tugasakhir::factory()->create(['email' => 'mhs@pps.test', 'email_dpl' => $dpl->email, 'nilai_dpl' => 88]);

        $this->put('dpl/destroy', ['id_dpl' => $dpl->id_dpl])->assertJson(['success' => true]);

        $this->assertDatabaseMissing('dpl', ['id_dpl' => $dpl->id_dpl]);
        $this->assertNull($ta->fresh()->email_dpl);
        $this->assertSame(88, $ta->fresh()->nilai_dpl);
    }

    public function test_import_rejects_non_spreadsheet_files(): void
    {
        $this->loginAs('admin');

        $this->from('mahasiswa')->put('mahasiswa/prosesimport', ['file' => UploadedFile::fake()->create('x.php', 5, 'text/x-php')])
            ->assertSessionHasErrors('file');
    }

    public function test_listing_shows_pt_and_lokasi(): void
    {
        $this->loginAs('admin');
        Mahasiswa::factory()->count(2)->create();

        $this->getJson('mahasiswa/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('data.0.nm_lemb', 'Belum Terdata');
    }
}
