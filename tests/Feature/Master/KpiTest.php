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

    public function test_kpi_target_and_satuan_are_validated_and_listed(): void
    {
        $this->loginAs('admin');

        $this->put('kpi/insert', ['nama_kpi' => 'KPI A', 'target' => -1])->assertJsonValidationErrors('target', 'errors');
        $this->put('kpi/insert', ['nama_kpi' => 'KPI A', 'target' => 'abc'])->assertJsonValidationErrors('target', 'errors');
        $this->put('kpi/insert', ['nama_kpi' => 'KPI A', 'target' => 100000000])->assertJsonValidationErrors('target', 'errors');
        $this->put('kpi/insert', ['nama_kpi' => 'KPI A', 'target' => 12.345])->assertJsonValidationErrors('target', 'errors');
        $this->put('kpi/insert', ['nama_kpi' => 'KPI A', 'satuan' => str_repeat('x', 51)])->assertJsonValidationErrors('satuan', 'errors');

        $this->put('kpi/insert', ['nama_kpi' => 'KPI A', 'target' => 12.5, 'satuan' => 'kg'])->assertJson(['success' => true]);
        $this->put('kpi/insert', ['nama_kpi' => 'KPI B'])->assertJson(['success' => true]);
        $kpi = Kpi::where('nama_kpi', 'KPI A')->firstOrFail();
        $this->assertEquals(12.5, (float) $kpi->target);
        $this->assertSame('kg', $kpi->satuan);
        $this->assertNull(Kpi::where('nama_kpi', 'KPI B')->value('target'));

        $this->put('kpi/update', ['id_kpi' => $kpi->id_kpi, 'nama_kpi' => 'KPI A', 'target' => 0, 'satuan' => 'rumah'])->assertJson(['success' => true]);
        $this->assertEquals(0, (float) $kpi->fresh()->target);

        $row = collect($this->getJson('kpi/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data'))->firstWhere('nama_kpi', 'KPI A');
        $this->assertSame('rumah', $row['satuan']);
        $this->assertEquals(0, (float) $row['target']);
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
