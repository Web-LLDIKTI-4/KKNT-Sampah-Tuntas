<?php

namespace Tests\Feature\Master;

use App\Models\Desa;
use App\Models\Desaprofile;
use App\Models\Kecamatan;
use App\Models\Mahasiswa_lokasi;
use App\Models\PenguranganSampah;
use App\Models\Pjdesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_desa(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago', 'satuan_baseline' => '%'])
            ->assertJson(['success' => true]);
        $desa = Desa::where('desa', 'Dago')->firstOrFail();

        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago Atas', 'satuan_baseline' => '%'])
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

        $this->put('desa/insert', ['id_kecamatan' => $desa->id_kecamatan, 'desa' => 'Sukamaju', 'satuan_baseline' => '%'])
            ->assertJsonPath('errors.desa.0', 'Data sudah ada!');
        $this->put('desa/insert', ['id_kecamatan' => $lain->id_kecamatan, 'desa' => 'Sukamaju', 'satuan_baseline' => '%'])
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

        $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Tanpa Baseline', 'satuan_baseline' => '%'])
            ->assertJson(['success' => true]);
        $this->assertNull(Desa::where('desa', 'Tanpa Baseline')->value('baseline'));

        $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago', 'satuan_baseline' => '%', 'baseline' => '12.5'])
            ->assertJson(['success' => true]);
        $desa = Desa::where('desa', 'Dago')->firstOrFail();
        $this->assertEquals(12.5, (float) $desa->baseline);

        $base = ['id_desa' => $desa->id_desa, 'id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Dago', 'satuan_baseline' => '%'];
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
            '-0.01' => 'Baseline tidak boleh kurang dari 0.',
            '100.01' => 'Baseline tidak boleh lebih dari 100.',
            '12.345' => 'Baseline maksimal 2 angka desimal.',
            'abc' => 'Baseline harus berupa angka.',
            '12,5' => 'Baseline harus berupa angka.',
        ];

        foreach ($cases as $input => $message) {
            $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Invalid '.$input, 'satuan_baseline' => '%', 'baseline' => $input])
                ->assertJsonPath('errors.baseline.0', $message);
        }
        $this->assertSame(0, Desa::where('desa', 'like', 'Invalid %')->count());

        $desa = Desa::factory()->create(['id_kecamatan' => $kecamatan->id_kecamatan, 'baseline' => 10]);
        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => $desa->desa, 'satuan_baseline' => '%', 'baseline' => '101'])
            ->assertJsonPath('errors.baseline.0', 'Baseline tidak boleh lebih dari 100.');
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

        $this->assertSame('12,50%', $rows['Alfa']['baseline']);
        $this->assertSame('-', $rows['Beta']['baseline']);
        $this->assertSame('12,34%', $rows['Gama']['baseline']);
    }

    public function test_listdata_view_has_baseline_column_matching_js_columns(): void
    {
        $this->loginAs('admin');
        $html = $this->get('desa/listdata')->assertOk()->assertSee('<th class="text-center text-nowrap">Baseline</th>', false)->getContent();

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

        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $desa->id_kecamatan, 'desa' => $desa->desa, 'satuan_baseline' => '%', 'baseline' => '50'])
            ->assertRedirect(route('home'));
        $this->assertEquals(10.0, (float) $desa->fresh()->baseline);
    }

    public function test_kg_baseline_saved_and_formatted_in_listdataserver(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Kilo', 'satuan_baseline' => 'kg', 'baseline' => '1250'])
            ->assertJson(['success' => true]);
        $desa = Desa::where('desa', 'Kilo')->firstOrFail();
        $this->assertSame('kg', $desa->satuan_baseline);
        $this->assertEquals(1250.0, (float) $desa->baseline);

        $base = ['id_desa' => $desa->id_desa, 'id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Kilo'];
        $this->put('desa/update', $base + ['satuan_baseline' => 'kg', 'baseline' => '150'])->assertJson(['success' => true]);
        $this->assertEquals(150.0, (float) $desa->fresh()->baseline);
        $this->put('desa/update', $base + ['satuan_baseline' => 'kg', 'baseline' => '1250'])->assertJson(['success' => true]);

        $rows = collect($this->getJson('desa/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data'))->keyBy('desa');
        $this->assertSame('1.250,00 kg', $rows['Kilo']['baseline']);

        $this->put('desa/update', $base + ['satuan_baseline' => '%', 'baseline' => '12.5'])->assertJson(['success' => true]);
        $this->assertSame('%', $desa->fresh()->satuan_baseline);
    }

    public function test_percent_baseline_above_100_rejected_but_kg_allowed(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();
        $base = ['id_kecamatan' => $kecamatan->id_kecamatan, 'baseline' => '150'];

        $this->put('desa/insert', $base + ['desa' => 'Persen', 'satuan_baseline' => '%'])
            ->assertJsonPath('errors.baseline.0', 'Baseline tidak boleh lebih dari 100.');
        $this->assertDatabaseMissing('desa', ['desa' => 'Persen']);

        $this->put('desa/insert', $base + ['desa' => 'Kilo', 'satuan_baseline' => 'kg'])->assertJson(['success' => true]);
        $this->assertEquals(150.0, (float) Desa::where('desa', 'Kilo')->value('baseline'));

        $this->put('desa/insert', ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Neg', 'satuan_baseline' => 'kg', 'baseline' => '-1'])
            ->assertJsonPath('errors.baseline.0', 'Baseline tidak boleh kurang dari 0.');

        $desa = Desa::factory()->create(['id_kecamatan' => $kecamatan->id_kecamatan, 'baseline' => 10]);
        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => $desa->desa] + $base + ['satuan_baseline' => '%'])
            ->assertJsonPath('errors.baseline.0', 'Baseline tidak boleh lebih dari 100.');
        $this->assertEquals(10.0, (float) $desa->fresh()->baseline);
    }

    public function test_satuan_baseline_is_required_and_must_be_percent_or_kg(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();
        $base = ['id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => 'Satuan Uji', 'baseline' => '10'];

        $this->put('desa/insert', $base)->assertJsonPath('errors.satuan_baseline.0', 'Satuan Baseline harus dipilih.');
        $this->put('desa/insert', $base + ['satuan_baseline' => ''])->assertJsonPath('errors.satuan_baseline.0', 'Satuan Baseline harus dipilih.');
        foreach (['ton', 'KG', 'persen', '<b>%</b>'] as $satuan) {
            $this->put('desa/insert', $base + ['satuan_baseline' => $satuan])
                ->assertJsonPath('errors.satuan_baseline.0', 'Satuan Baseline tidak valid.');
        }
        $this->assertDatabaseMissing('desa', ['desa' => 'Satuan Uji']);

        $desa = Desa::factory()->create(['id_kecamatan' => $kecamatan->id_kecamatan]);
        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $kecamatan->id_kecamatan, 'desa' => $desa->desa, 'satuan_baseline' => 'ton'])
            ->assertJsonPath('errors.satuan_baseline.0', 'Satuan Baseline tidak valid.');
        $this->assertSame('%', $desa->fresh()->satuan_baseline);
    }

    public function test_desa_form_has_satuan_baseline_select(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create(['satuan_baseline' => 'kg', 'baseline' => 1250]);

        $this->get('desa/edit/'.$desa->id_desa)->assertOk()
            ->assertSee('Baseline</label>', false)->assertSee('Satuan</label>', false)->assertSee('name="satuan_baseline"', false)
            ->assertSee('<option value="kg" selected', false);
    }

    public function test_non_admin_cannot_set_satuan_baseline(): void
    {
        $desa = Desa::factory()->create(['baseline' => 10]);
        $this->loginAs('mahasiswa');

        $this->put('desa/update', ['id_desa' => $desa->id_desa, 'id_kecamatan' => $desa->id_kecamatan, 'desa' => $desa->desa, 'satuan_baseline' => 'kg', 'baseline' => '500'])
            ->assertRedirect(route('home'));
        $this->assertSame('%', $desa->fresh()->satuan_baseline);
        $this->assertEquals(10.0, (float) $desa->fresh()->baseline);
    }

    // Legacy rows were created before HasUuids, so id_desa may be non-UUID
    private function legacyDesa(string $id): string
    {
        DB::table('desa')->insert([
            'id_desa' => $id,
            'id_kecamatan' => Kecamatan::factory()->create()->id_kecamatan,
            'desa' => 'Legacy '.$id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_destroy_deletes_uuid_desa(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create();
        $this->assertTrue(Str::isUuid($desa->id_desa));

        $this->put('desa/destroy', ['id_desa' => $desa->id_desa])->assertOk()
            ->assertJson(['success' => true, 'message' => 'Data berhasil dihapus']);
        $this->assertDatabaseMissing('desa', ['id_desa' => $desa->id_desa]);
    }

    public function test_destroy_deletes_legacy_non_uuid_desa(): void
    {
        foreach (['admin' => 'desa-lama-01', 'pemda' => '17'] as $role => $id) {
            $this->loginAs($role);
            $this->legacyDesa($id);
            $keep = Desa::factory()->create();

            $this->put('desa/destroy', ['id_desa' => $id])->assertOk()->assertJson(['success' => true]);
            $this->assertDatabaseMissing('desa', ['id_desa' => $id]);
            $this->assertDatabaseHas('desa', ['id_desa' => $keep->id_desa]);
        }
    }

    public function test_destroy_unknown_id_returns_json_not_found(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create();

        foreach ([(string) Str::uuid(), 'tidak-ada', '99999'] as $id) {
            $this->put('desa/destroy', ['id_desa' => $id])->assertNotFound()
                ->assertExactJson(['success' => false, 'message' => 'Data tidak ditemukan!']);
        }
        $this->assertDatabaseHas('desa', ['id_desa' => $desa->id_desa]);
    }

    public function test_destroy_rejects_array_or_empty_id_without_server_error(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create();
        $legacy = $this->legacyDesa('desa-lama-02');

        $payloads = [
            ['id_desa' => [$desa->id_desa]],
            ['id_desa' => [$desa->id_desa, $legacy]],
            ['id_desa' => ['x' => [$desa->id_desa]]],
            ['id_desa' => ''],
            ['id_desa' => '   '],
            ['id_desa' => null],
            [],
        ];
        foreach ($payloads as $payload) {
            $this->put('desa/destroy', $payload)->assertNotFound()->assertJson(['success' => false]);
        }
        $this->putJson('desa/destroy', ['id_desa' => [$desa->id_desa]])->assertNotFound()->assertJson(['success' => false]);

        $this->assertDatabaseHas('desa', ['id_desa' => $desa->id_desa]);
        $this->assertDatabaseHas('desa', ['id_desa' => $legacy]);
    }

    public function test_destroy_rejects_desa_with_related_data(): void
    {
        $this->loginAs('admin');
        $related = [
            'profil desa' => fn (string $id) => Desaprofile::factory()->create(['id_desa' => $id]),
            'pj desa' => fn (string $id) => Pjdesa::create(['email' => 'pj@example.test', 'id_desa' => $id]),
            'lokasi mahasiswa' => fn (string $id) => Mahasiswa_lokasi::create(['tahun' => now()->year, 'id_mahasiswa' => (string) Str::uuid(), 'id_desa' => $id]),
            'pengurangan sampah' => fn (string $id) => PenguranganSampah::create([
                'id_sampah' => (string) Str::uuid(),
                'email' => 'ketua-'.Str::random(8).'@example.test',
                'id_desa' => $id,
                'bulan' => now()->startOfMonth()->toDateString(),
                'jml_rw' => 1, 'jml_penduduk' => 1, 'jml_rumah' => 1, 'jml_rumah_memilah' => 0,
                'timbulan' => 0, 'organik_sumber' => 0, 'organik_dlh' => 0,
                'anorganik_sumber' => 0, 'pengurangan' => 0, 'belum_terkelola' => 0,
            ]),
        ];

        foreach ($related as $label => $attach) {
            foreach ([Desa::factory()->create()->id_desa, $this->legacyDesa('lama-'.Str::slug($label))] as $id) {
                $attach($id);
                $this->put('desa/destroy', ['id_desa' => $id])->assertOk()
                    ->assertJson(['success' => false, 'error' => 'Data gagal dihapus karena terkait dengan data lain']);
                $this->assertDatabaseHas('desa', ['id_desa' => $id]);
            }
        }
    }

    public function test_destroy_is_restricted_to_admin_and_pemda(): void
    {
        $desa = Desa::factory()->create();
        $legacy = $this->legacyDesa('desa-lama-03');

        $this->put('desa/destroy', ['id_desa' => $legacy])->assertRedirect(route('login'));

        $this->loginAs('kepala');
        $this->put('desa/destroy', ['id_desa' => $desa->id_desa])->assertForbidden();
        $this->put('desa/destroy', ['id_desa' => $legacy])->assertForbidden();

        foreach (['pt', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            $this->put('desa/destroy', ['id_desa' => $desa->id_desa])->assertRedirect(route('home'));
            $this->put('desa/destroy', ['id_desa' => $legacy])->assertRedirect(route('home'));
        }

        $this->assertDatabaseHas('desa', ['id_desa' => $desa->id_desa]);
        $this->assertDatabaseHas('desa', ['id_desa' => $legacy]);
    }
}
