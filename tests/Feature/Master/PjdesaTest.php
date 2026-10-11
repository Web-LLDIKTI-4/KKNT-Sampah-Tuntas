<?php

namespace Tests\Feature\Master;

use App\Models\Desa;
use App\Models\CapaianKegiatan;
use App\Models\Pjdesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PjdesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_destroy_removes_pjdesa_and_revokes_akses(): void
    {
        $this->loginAs('admin');
        $ketua = User::factory()->create(['akses' => 'pjdesa']);
        $pj = Pjdesa::create(['id_desa' => Desa::factory()->create()->id_desa, 'email' => $ketua->email]);

        $this->put('pjdesa/destroy', ['id_pjdesa' => $pj->id_pjdesa])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('pj_desa', ['id_pjdesa' => $pj->id_pjdesa]);
        $this->assertNull($ketua->fresh()->akses);
    }

    public function test_pjdesa_with_capaian_cannot_be_deleted(): void
    {
        $this->loginAs('admin');
        $pj = Pjdesa::create(['id_desa' => Desa::factory()->create()->id_desa, 'email' => 'ketua@pps.test']);
        CapaianKegiatan::factory()->create([
            'email' => 'ketua@pps.test',
            'id_pjdesa' => $pj->id_pjdesa,
            'bulan' => now()->startOfMonth()->toDateString(),
        ]);

        $this->put('pjdesa/destroy', ['id_pjdesa' => $pj->id_pjdesa])->assertJson(['success' => false]);
        $this->assertDatabaseHas('pj_desa', ['id_pjdesa' => $pj->id_pjdesa]);
    }

    public function test_last_year_capaian_does_not_block_deletion(): void
    {
        $this->loginAs('admin');
        $pj = Pjdesa::create(['id_desa' => Desa::factory()->create()->id_desa, 'email' => 'ketua@pps.test']);
        $capaian = CapaianKegiatan::factory()->create([
            'email' => 'ketua@pps.test',
            'id_pjdesa' => $pj->id_pjdesa,
            'bulan' => now()->subYear()->startOfMonth()->toDateString(),
        ]);

        $this->put('pjdesa/destroy', ['id_pjdesa' => $pj->id_pjdesa])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('pj_desa', ['id_pjdesa' => $pj->id_pjdesa]);
        $this->assertDatabaseHas('capaian_kegiatan', ['email' => 'ketua@pps.test', 'bulan' => $capaian->bulan]);
    }

    public function test_destroy_keeps_akses_when_other_assignment_remains(): void
    {
        $this->loginAs('admin');
        $ketua = User::factory()->create(['akses' => 'pjdesa']);
        $pj = Pjdesa::create(['id_desa' => Desa::factory()->create()->id_desa, 'email' => $ketua->email]);
        Pjdesa::create(['id_desa' => Desa::factory()->create()->id_desa, 'email' => $ketua->email]);

        $this->put('pjdesa/destroy', ['id_pjdesa' => $pj->id_pjdesa])->assertJson(['success' => true]);
        $this->assertSame('pjdesa', $ketua->fresh()->akses);
    }
}
