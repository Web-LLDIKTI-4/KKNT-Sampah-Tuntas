<?php

namespace Tests\Feature\Master;

use App\Models\Kpi;
use App\Models\Kpicapaian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class KpiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_kpi(): void
    {
        $this->loginAs('admin');

        $this->put('kpi/insert', ['nama_kpi' => 'Digitalisasi UMKM'])->assertJson(['success' => true]);
        $this->put('kpi/insert', ['nama_kpi' => 'Digitalisasi UMKM'])
            ->assertJsonPath('errors.nama_kpi.0', 'Key performance indicator sudah ada!');

        $kpi = Kpi::where('nama_kpi', 'Digitalisasi UMKM')->firstOrFail();
        $this->put('kpi/update', ['id_kpi' => $kpi->id_kpi, 'nama_kpi' => 'Digitalisasi Desa'])->assertJson(['success' => true]);
        $this->put('kpi/destroy', ['id_kpi' => $kpi->id_kpi])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('kpi', ['id_kpi' => $kpi->id_kpi]);
    }

    public function test_kpi_with_capaian_cannot_be_deleted(): void
    {
        $this->loginAs('admin');
        $capaian = Kpicapaian::factory()->create(['id_kpi' => Kpi::factory()->create()->id_kpi]);

        $this->put('kpi/destroy', ['id_kpi' => $capaian->id_kpi])->assertJson(['success' => false]);
    }

    public function test_exports_download(): void
    {
        Excel::fake();
        $this->loginAs('admin');

        $this->get('kpi/export')->assertOk();
    }
}
