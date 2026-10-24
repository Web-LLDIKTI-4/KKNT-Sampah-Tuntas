<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\Tugasakhir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PtTugasakhirMenuTest extends TestCase
{
    use RefreshDatabase;

    private function menuLink(): string
    {
        return 'href="'.url('pttugasakhir').'"';
    }

    public function test_pt_sees_laporan_akhir_menu(): void
    {
        $this->loginAs('pt', ['email' => Satuanpendidikan::factory()->create()->npsn]);

        $this->get('home')->assertOk()->assertSee($this->menuLink(), false)->assertSee('Laporan Akhir');
        $this->get('pttugasakhir')->assertOk();
    }

    public function test_other_roles_do_not_see_laporan_akhir_menu(): void
    {
        foreach (['admin', 'kepala', 'pemda', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);

            $this->get('home')->assertDontSee($this->menuLink(), false);
            $this->get('pttugasakhir')->assertRedirect(route('home'));
        }
    }

    public function test_pttugasakhir_lists_only_own_pt_students(): void
    {
        $ptA = Satuanpendidikan::factory()->create();
        $ptB = Satuanpendidikan::factory()->create();
        $own = Mahasiswa::factory()->count(2)->create(['kodept' => $ptA->npsn]);
        $foreign = Mahasiswa::factory()->create(['kodept' => $ptB->npsn]);
        Tugasakhir::factory()->create(['email' => $own[0]->email, 'tautan' => 'https://contoh.test/ta-a']);
        Tugasakhir::factory()->create(['email' => $foreign->email, 'tautan' => 'https://contoh.test/ta-b']);
        $this->loginAs('pt', ['email' => $ptA->npsn]);

        $response = $this->getJson('pttugasakhir/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 2);

        $emails = collect($response->json('data'))->pluck('email');
        $this->assertEqualsCanonicalizing($own->pluck('email')->all(), $emails->all());
        $this->assertStringNotContainsString('ta-b', $response->getContent());
    }
}
