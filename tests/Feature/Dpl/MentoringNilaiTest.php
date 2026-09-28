<?php

namespace Tests\Feature\Dpl;

use App\Models\Dplmentoring;
use App\Models\Freeform;
use App\Models\Mahasiswa;
use App\Models\Nilaikonversi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentoringNilaiTest extends TestCase
{
    use RefreshDatabase;

    private function bimbingan($dpl): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create();
        Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);

        return $mhs;
    }

    public function test_dpl_can_claim_unassigned_students_only(): void
    {
        $dpl = $this->loginAs('dpl');
        $bebas = Mahasiswa::factory()->create();
        $sudahAda = Mahasiswa::factory()->create();
        Dplmentoring::create(['email_mahasiswa' => $sudahAda->email, 'email_dpl' => 'dpl-lain@pps.test']);

        $this->put('dplmentoring/insert', ['createuser' => [$bebas->email, $sudahAda->email, 'hantu@pps.test']])
            ->assertJson(['success' => '1 mahasiswa berhasil ditambahkan']);

        $this->assertTrue(Dplmentoring::isMentor($dpl, $bebas->email));
        $this->assertFalse(Dplmentoring::isMentor($dpl, $sudahAda->email));
        $this->put('dplmentoring/insert', [])->assertJsonStructure(['error']);
    }

    public function test_dpl_cannot_delete_or_view_other_dpl_mentees(): void
    {
        $this->loginAs('dpl');
        $mhsLain = Mahasiswa::factory()->create();
        $relasiLain = Dplmentoring::create(['email_mahasiswa' => $mhsLain->email, 'email_dpl' => 'dpl-lain@pps.test']);

        $this->put('dplmentoring/destroy/'.$relasiLain->id_mentoring)->assertNotFound();
        $this->get('dplmentoring/rekapnilai/'.base64_encode($mhsLain->email))->assertNotFound();
        $this->get('dplmentoring/nilaikonversi/'.$mhsLain->id_mahasiswa)->assertNotFound();
        $this->get('dplmentoring/freeform/'.$mhsLain->id_mahasiswa)->assertNotFound();
        $this->assertDatabaseHas('dpl_mentoring', ['id_mentoring' => $relasiLain->id_mentoring]);
    }

    public function test_konversi_only_for_own_mentee_and_crud_works(): void
    {
        $dpl = $this->loginAs('dpl');
        $mhs = $this->bimbingan($dpl);
        $bukan = Mahasiswa::factory()->create();
        $payload = ['id_mahasiswa' => $mhs->id_mahasiswa, 'matakuliah' => 'KKN Tematik', 'sks' => 3, 'nilai_dpl' => 85];

        $this->put('dplkonversinilai/insert', ['id_mahasiswa' => $bukan->id_mahasiswa] + $payload)
            ->assertJsonPath('errors.id_mahasiswa.0', 'Mahasiswa bukan bimbingan Anda.');
        $this->put('dplkonversinilai/insert', ['nilai_dpl' => 150] + $payload)->assertJsonValidationErrors('nilai_dpl', 'errors');
        $this->put('dplkonversinilai/insert', $payload)->assertJson(['success' => true]);
        $this->put('dplkonversinilai/insert', $payload)->assertJsonPath('errors.matakuliah.0', 'Matakuliah sudah terdata pada mahasiswa ini');

        $nilai = Nilaikonversi::firstOrFail();
        $this->assertSame($dpl->email, $nilai->email_dpl);
        $this->put('dplkonversinilai/update', ['id_konversi' => $nilai->id_konversi, 'nilai_dpl' => 90] + $payload)->assertJson(['success' => true]);
        $this->assertSame('90', $nilai->fresh()->nilai_dpl);
        $this->put('dplkonversinilai/destroy', ['id_konversi' => $nilai->id_konversi])->assertJson(['success' => true]);
    }

    public function test_dpl_cannot_edit_other_dpl_freeform(): void
    {
        $this->loginAs('dpl');
        $milikLain = Freeform::factory()->create(['email_dpl' => 'dpl-lain@pps.test', 'id_mahasiswa' => Mahasiswa::factory()->create()->id_mahasiswa]);

        $this->get('dplfreeform/edit/'.$milikLain->id_freeform)->assertNotFound();
        $this->put('dplfreeform/destroy', ['id_freeform' => $milikLain->id_freeform])->assertNotFound();
    }

    public function test_freeform_component_must_be_from_list(): void
    {
        $dpl = $this->loginAs('dpl');
        $mhs = $this->bimbingan($dpl);

        $this->put('dplfreeform/insert', ['id_mahasiswa' => $mhs->id_mahasiswa, 'freeform' => 'Bebas', 'nilai_dpl' => 80])
            ->assertJsonPath('errors.freeform.0', 'Komponen free form tidak valid.');
        $this->put('dplfreeform/insert', ['id_mahasiswa' => $mhs->id_mahasiswa, 'freeform' => Freeform::KOMPONEN[0], 'nilai_dpl' => 80])
            ->assertJson(['success' => true]);
    }

    public function test_pt_sees_only_its_students_values(): void
    {
        $pt = $this->loginAs('pt', ['email' => '041111']);
        $mhsPt = Mahasiswa::factory()->create(['kodept' => '041111', 'location_program' => $pt->location_program]);
        Nilaikonversi::factory()->create(['id_mahasiswa' => $mhsPt->id_mahasiswa, 'email_dpl' => 'dpl@pps.test']);
        Nilaikonversi::factory()->create(['id_mahasiswa' => Mahasiswa::factory()->create()->id_mahasiswa, 'email_dpl' => 'dpl@pps.test']);

        $this->getJson('dplkonversinilai/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 1);
    }
}
