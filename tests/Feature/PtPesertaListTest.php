<?php

namespace Tests\Feature;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PtPesertaListTest extends TestCase
{
    use RefreshDatabase;

    private Satuanpendidikan $ptA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ptA = Satuanpendidikan::factory()->create();
        $ptB = Satuanpendidikan::factory()->create();

        Dpl::factory()->count(2)->create(['kodept' => $this->ptA->npsn]);
        Dpl::factory()->create(['kodept' => $ptB->npsn]);
        Mahasiswa::factory()->count(2)->create(['kodept' => $this->ptA->npsn]);
        Mahasiswa::factory()->count(3)->create(['kodept' => $ptB->npsn]);
    }

    private function listJson(string $prefix)
    {
        return $this->getJson($prefix.'/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_pt_only_sees_own_dpl_and_mahasiswa(): void
    {
        $this->loginAs('pt', ['email' => $this->ptA->npsn]);

        $this->get('ptdpl')->assertOk();
        $this->listJson('ptdpl')->assertOk()->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('data.0.nm_lemb', $this->ptA->nm_lemb);
        $this->listJson('ptmahasiswa')->assertOk()->assertJsonPath('recordsTotal', 2);
    }

    public function test_kepala_sees_all_dpl_and_mahasiswa(): void
    {
        $this->loginAs('kepala');

        $this->listJson('ptdpl')->assertOk()->assertJsonPath('recordsTotal', 3);
        $this->listJson('ptmahasiswa')->assertOk()->assertJsonPath('recordsTotal', 5);
    }

    public function test_other_roles_and_non_ajax_are_rejected(): void
    {
        $this->loginAs('dpl');
        $this->get('ptdpl')->assertRedirect(route('home'));

        $this->loginAs('kepala');
        $this->get('ptdpl/listdataserver')->assertNotFound();
    }
}
