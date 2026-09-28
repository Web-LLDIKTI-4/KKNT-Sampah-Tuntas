<?php

namespace Tests\Feature\Master;

use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpitarget;
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

    public function test_kpi_with_target_cannot_be_deleted(): void
    {
        $this->loginAs('admin');
        $target = Kpitarget::factory()->create(['id_kpi' => Kpi::factory()->create()->id_kpi]);

        $this->put('kpi/destroy', ['id_kpi' => $target->id_kpi])->assertJson(['success' => false]);
    }

    public function test_admin_can_manage_target(): void
    {
        $this->loginAs('admin');
        $kpi = Kpi::factory()->create();
        $payload = ['id_kpi' => $kpi->id_kpi, 'tahapan' => 'Tahap 1', 'nama_kpitarget' => 'Pelatihan', 'target' => 25, 'satuan' => '%'];

        $this->put('kpitarget/insert', $payload)->assertJson(['success' => true]);
        $this->put('kpitarget/insert', $payload)
            ->assertJsonPath('errors.nama_kpitarget.0', 'Kegiatan sudah ada!');
        $this->put('kpitarget/insert', ['target' => 0] + $payload)->assertJsonValidationErrors('target', 'errors');
        $this->put('kpitarget/insert', ['satuan' => ''] + $payload)->assertJsonValidationErrors('satuan', 'errors');

        $target = Kpitarget::firstOrFail();
        $this->put('kpitarget/update', ['id_target' => $target->id_target, 'target' => 2, 'satuan' => 'Kegiatan'] + $payload)->assertJson(['success' => true]);
        $this->assertSame('2.00', $target->fresh()->target);
        $this->assertSame('Kegiatan', $target->fresh()->satuan);
    }

    public function test_target_with_capaian_cannot_be_deleted(): void
    {
        $this->loginAs('admin');
        $target = Kpitarget::factory()->create(['id_kpi' => Kpi::factory()->create()->id_kpi]);
        Kpicapaian::factory()->create(['id_kpi' => $target->id_kpi, 'id_target' => $target->id_target]);

        $this->put('kpitarget/destroy', ['id_target' => $target->id_target])->assertJson(['success' => false]);
    }

    public function test_exports_download(): void
    {
        Excel::fake();
        $this->loginAs('admin');

        $this->get('kpi/export')->assertOk();
        $this->get('kpitarget/export')->assertOk();
    }
}
