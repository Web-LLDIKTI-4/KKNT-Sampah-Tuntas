<?php

namespace Tests\Feature\Master;

use App\Models\Desa;
use App\Models\Desaprofile;
use App\Models\Kecamatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_desa(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago'])
            ->assertJson(['success' => true]);
        $desa = Desa::where('desa', 'Dago')->firstOrFail();

        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago Atas'])
            ->assertJson(['success' => true]);
        $this->assertSame('Dago Atas', $desa->fresh()->desa);

        $this->put('desa/destroy', ['id_desa' => $desa->id_desa])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('desa', ['id_desa' => $desa->id_desa]);
    }

    public function test_same_name_is_allowed_in_other_kecamatan_only(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create(['desa' => 'Sukamaju']);
        $lain = Kecamatan::factory()->create();

        $this->put('desa/insert', ['id_kecamatan' => $desa->id_kecamatan, 'desa' => 'Sukamaju'])
            ->assertJsonPath('errors.desa.0', 'Data sudah ada!');
        $this->put('desa/insert', ['id_kecamatan' => $lain->id_kecamatan, 'desa' => 'Sukamaju'])
            ->assertJson(['success' => true]);
    }

    public function test_desa_with_profile_cannot_be_deleted(): void
    {
        $this->loginAs('admin');
        $profile = Desaprofile::factory()->create();

        $this->put('desa/destroy', ['id_desa' => $profile->id_desa])->assertJson(['success' => false]);
        $this->assertDatabaseHas('desa', ['id_desa' => $profile->id_desa]);
    }

    public function test_listdataserver_includes_kecamatan_name(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create();

        $this->getJson('desa/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonFragment(['kecamatan' => $desa->kecamatan->kecamatan]);
    }

    public function test_baseline_is_optional_and_saved_on_insert_and_update(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Tanpa Baseline'])
            ->assertJson(['success' => true]);
        $this->assertNull(Desa::where('desa', 'Tanpa Baseline')->value('baseline'));

        $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago', 'baseline' => '12.5'])
            ->assertJson(['success' => true]);
        $desa = Desa::where('desa', 'Dago')->firstOrFail();
        $this->assertEquals(12.5, (float) $desa->baseline);

        $base = ['id_desa' => $desa->id_desa, 'id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago'];
        foreach (['0' => 0.0, '100' => 100.0, '99.99' => 99.99] as $input => $expected) {
            $this->put('desa/update', $base + ['baseline' => $input])->assertJson(['success' => true]);
            $this->assertEquals($expected, (float) $desa->fresh()->baseline, "baseline {$input}");
        }

        $this->put('desa/update', $base + ['baseline' => ''])->assertJson(['success' => true]);
        $this->assertNull($desa->fresh()->baseline);
    }

    public function test_baseline_validation_rejects_invalid_values(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();
        $cases = [
            '-0.01' => 'Base Line harus di antara 0 dan 100.',
            '100.01' => 'Base Line harus di antara 0 dan 100.',
            '12.345' => 'Base Line maksimal 2 angka desimal.',
            'abc' => 'Base Line harus berupa angka.',
            '12,5' => 'Base Line harus berupa angka.',
        ];

        foreach ($cases as $input => $message) {
            $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Invalid '.$input, 'baseline' => $input])
                ->assertJsonPath('errors.baseline.0', $message);
        }
        $this->assertSame(0, Desa::where('desa', 'like', 'Invalid %')->count());

        $desa = Desa::factory()->create(['id_kecamatan' => $kecamatan->id_kecamatan, 'baseline' => 10]);
        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => $desa->desa, 'baseline' => '101'])
            ->assertJsonPath('errors.baseline.0', 'Base Line harus di antara 0 dan 100.');
        $this->assertEquals(10.0, (float) $desa->fresh()->baseline);
    }

    public function test_listdataserver_formats_baseline(): void
    {
        $this->loginAs('admin');
        Desa::factory()->create(['desa' => 'Alfa', 'baseline' => 12.5]);
        Desa::factory()->create(['desa' => 'Beta', 'baseline' => null]);
        Desa::factory()->create(['desa' => 'Gama', 'baseline' => 1234 / 100]);

        $rows = collect($this->getJson('desa/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data'))->keyBy('desa');

        $this->assertSame('12,50 %', $rows['Alfa']['baseline']);
        $this->assertSame('-', $rows['Beta']['baseline']);
        $this->assertSame('12,34 %', $rows['Gama']['baseline']);
    }

    public function test_listdata_view_has_baseline_column_matching_js_columns(): void
    {
        $this->loginAs('admin');
        $html = $this->get('desa/listdata')->assertOk()->assertSee('Base Line (%)')->getContent();

        preg_match('#<thead>(.*?)</thead>#s', $html, $thead);
        $thCount = substr_count($thead[1] ?? '', '<th');
        preg_match('#columns\s*:\s*\[(.*?)\]\s*,?\s*(?:columnDefs|order|drawCallback|\})#s', $html, $cols);
        $jsCount = preg_match_all('#\{\s*data\s*:#', $cols[1] ?? '');

        $this->assertGreaterThan(0, $thCount);
        $this->assertSame($thCount, $jsCount, 'jumlah <th> harus sama dengan jumlah kolom DataTables');
        $this->assertStringContainsString("data: 'baseline'", preg_replace('/"baseline"/', "'baseline'", $cols[1] ?? ''));
    }

    public function test_non_admin_cannot_set_baseline(): void
    {
        $desa = Desa::factory()->create(['baseline' => 10]);
        $this->loginAs('mahasiswa');

        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $desa->id_kecamatan, 'desa' => $desa->desa, 'baseline' => '50'])
            ->assertRedirect(route('home'));
        $this->assertEquals(10.0, (float) $desa->fresh()->baseline);
    }
}
