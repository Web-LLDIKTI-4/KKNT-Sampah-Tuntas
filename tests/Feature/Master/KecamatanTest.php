<?php

namespace Tests\Feature\Master;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Picdlh;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class KecamatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_kecamatan(): void
    {
        $this->loginAs('admin');

        $this->put('kecamatan/insert', ['kecamatan' => 'Coblong'])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('kecamatan', ['kecamatan' => 'Coblong']);
    }

    public function test_duplicate_and_empty_name_are_rejected(): void
    {
        $this->loginAs('admin');
        Kecamatan::factory()->create(['kecamatan' => 'Coblong']);

        $this->put('kecamatan/insert', ['kecamatan' => 'Coblong'])
            ->assertOk()->assertJson(['success' => false])->assertJsonPath('errors.kecamatan.0', 'Data sudah ada!');

        $this->put('kecamatan/insert', ['kecamatan' => ''])
            ->assertJsonPath('errors.kecamatan.0', 'Nama kecamatan harus diisi.');
    }

    public function test_admin_can_update_kecamatan(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        $this->put('kecamatan/update', ['id_kecamatan' => $kecamatan->id_kecamatan, 'kecamatan' => 'Sukajadi'])
            ->assertJson(['success' => true]);

        $this->assertSame('Sukajadi', $kecamatan->fresh()->kecamatan);
    }

    public function test_update_requires_valid_id(): void
    {
        $this->loginAs('admin');

        $this->put('kecamatan/update', ['id_kecamatan' => 'bukan-uuid', 'kecamatan' => 'X'])
            ->assertJson(['success' => false])->assertJsonValidationErrors('id_kecamatan', 'errors');
    }

    public function test_delete_is_rejected_when_kecamatan_has_desa(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create();

        $this->put('kecamatan/destroy', ['id_kecamatan' => $desa->id_kecamatan])
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('kecamatan', ['id_kecamatan' => $desa->id_kecamatan]);
    }

    public function test_admin_can_delete_unused_kecamatan(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        $this->put('kecamatan/destroy', ['id_kecamatan' => $kecamatan->id_kecamatan])
            ->assertJson(['success' => true, 'message' => 'Data berhasil dihapus']);

        $this->assertDatabaseMissing('kecamatan', ['id_kecamatan' => $kecamatan->id_kecamatan]);
    }

    public function test_listdataserver_returns_datatables_json(): void
    {
        $this->loginAs('admin');
        Kecamatan::factory()->count(3)->create();

        $this->getJson('kecamatan/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 3);
    }

    public function test_listdataserver_default_order_is_nama_kecamatan(): void
    {
        $this->loginAs('admin');
        foreach (['Cicendo', 'Andir', 'Bojongloa'] as $nama) {
            Kecamatan::factory()->create(['kecamatan' => $nama]);
        }

        // DataTables default sends order on column 0 (No, orderable=false)
        $query = 'kecamatan/listdataserver?draw=1&start=0&length=10&order[0][column]=0&order[0][dir]=desc'
            .'&columns[0][data]=DT_RowIndex&columns[0][name]=DT_RowIndex&columns[0][orderable]=false&columns[0][searchable]=false'
            .'&columns[1][data]=kecamatan&columns[1][name]=kecamatan&columns[1][orderable]=true&columns[1][searchable]=true';
        $nama = collect($this->getJson($query, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->pluck('kecamatan')->all();

        $this->assertSame(['Andir', 'Bojongloa', 'Cicendo'], $nama);
    }

    public function test_listdataserver_malformed_order_does_not_500(): void
    {
        $this->loginAs('admin');
        Kecamatan::factory()->count(2)->create();

        // >50 columns: extras are dropped, order pointing at a dropped column is ignored
        $many = implode('&', array_map(fn ($i) => "columns[$i][data]=x$i", range(0, 59))).'&order[0][column]=55';

        foreach ([
            'order=x',
            'order[0][dir][]=x',
            'order[0][column]=1&columns[1]=x',
            'columns=x',
            'columns[0][search]=x',
            'columns[0][data][]=x&columns[0][searchable]=true',
            'columns[0][data]=nama&columns[0][searchable]=true&columns[0][search][value][]=x',
            'columns[x][data]=nama&order[0][column]=x',
            'columns[0][data]=a;b&columns[0][name]=a;b&columns[0][orderable]=true&order[0][column]=0&order[0][dir]=asc',
            'search=x',
            'search[value][]=x',
            'columns[-1][data]=kecamatan&columns[-1][orderable]=true&order[0][column]=-1&order[0][dir]=asc',
            'columns[99999][data]=kecamatan&columns[99999][orderable]=true&order[0][column]=99999',
            'columns[99999999999999999999][data]=kecamatan&order[0][column]=99999999999999999999',
            'columns[7][data]=kecamatan&columns[7][orderable]=true&columns[3][data]=kecamatan&order[0][column]=7&order[0][dir]=asc',
            'columns[0][search][regex]=true&search[regex]=true',
            'columns[0][data]=kecamatan%0A&columns[0][name]=kecamatan%0A&columns[0][orderable]=true&order[0][column]=0',
            'searchPanes[kecamatan][]=x',
            $many,
        ] as $order) {
            $this->getJson('kecamatan/listdataserver?draw=1&start=0&length=10&'.$order, ['X-Requested-With' => 'XMLHttpRequest'])
                ->assertOk()->assertJsonCount(2, 'data');
        }

        // 2500-term search is capped to 100 chars / 10 terms
        $start = microtime(true);
        $this->getJson('kecamatan/listdataserver?draw=1&start=0&length=10&columns[0][data]=kecamatan&columns[0][searchable]=true&columns[0][search][value]='.str_repeat('a+', 2500).'&search[value]='.str_repeat('a+', 2500), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();
        $this->assertLessThan(2, microtime(true) - $start);

        // regex=true is forced off: '[' becomes a LIKE match instead of a REGEXP error
        $this->getJson('kecamatan/listdataserver?draw=1&start=0&length=10&columns[0][data]=kecamatan&columns[0][searchable]=true&columns[0][search][value]=%5B&columns[0][search][regex]=true', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_edit_missing_record_returns_404(): void
    {
        $this->loginAs('admin');

        $this->get('kecamatan/edit/'.fake()->uuid())->assertNotFound();
    }

    public function test_non_admin_cannot_modify_kecamatan(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('kecamatan/insert', ['kecamatan' => 'Coblong'])->assertRedirect(route('home'));
        $this->assertDatabaseMissing('kecamatan', ['kecamatan' => 'Coblong']);
    }

    // Legacy rows were created before HasUuids, so id_kecamatan may be non-UUID
    private function legacyKecamatan(string $id): string
    {
        DB::table('kecamatan')->insert([
            'id_kecamatan' => $id,
            'kecamatan' => 'Legacy '.$id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_destroy_deletes_uuid_kecamatan(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();
        $this->assertTrue(Str::isUuid($kecamatan->id_kecamatan));

        $this->put('kecamatan/destroy', ['id_kecamatan' => $kecamatan->id_kecamatan])->assertOk()
            ->assertJson(['success' => true, 'message' => 'Data berhasil dihapus']);
        $this->assertDatabaseMissing('kecamatan', ['id_kecamatan' => $kecamatan->id_kecamatan]);
    }

    public function test_destroy_deletes_legacy_non_uuid_kecamatan(): void
    {
        foreach (['admin' => 'kec-lama-01', 'pemda' => '17'] as $role => $id) {
            $this->loginAs($role);
            $this->legacyKecamatan($id);
            $keep = Kecamatan::factory()->create();

            $this->put('kecamatan/destroy', ['id_kecamatan' => $id])->assertOk()->assertJson(['success' => true]);
            $this->assertDatabaseMissing('kecamatan', ['id_kecamatan' => $id]);
            $this->assertDatabaseHas('kecamatan', ['id_kecamatan' => $keep->id_kecamatan]);
        }
    }

    public function test_destroy_unknown_id_returns_json_not_found(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        foreach ([(string) Str::uuid(), 'tidak-ada', '99999'] as $id) {
            $this->put('kecamatan/destroy', ['id_kecamatan' => $id])->assertNotFound()
                ->assertExactJson(['success' => false, 'message' => 'Data tidak ditemukan!']);
        }
        $this->assertDatabaseHas('kecamatan', ['id_kecamatan' => $kecamatan->id_kecamatan]);
    }

    public function test_destroy_rejects_array_or_empty_id_without_server_error(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();
        $legacy = $this->legacyKecamatan('kec-lama-02');

        $payloads = [
            ['id_kecamatan' => [$kecamatan->id_kecamatan]],
            ['id_kecamatan' => [$kecamatan->id_kecamatan, $legacy]],
            ['id_kecamatan' => ['x' => [$kecamatan->id_kecamatan]]],
            ['id_kecamatan' => ''],
            ['id_kecamatan' => '   '],
            ['id_kecamatan' => null],
            [],
        ];
        foreach ($payloads as $payload) {
            $this->put('kecamatan/destroy', $payload)->assertNotFound()->assertJson(['success' => false]);
        }
        $this->putJson('kecamatan/destroy', ['id_kecamatan' => [$kecamatan->id_kecamatan]])->assertNotFound()->assertJson(['success' => false]);

        $this->assertDatabaseHas('kecamatan', ['id_kecamatan' => $kecamatan->id_kecamatan]);
        $this->assertDatabaseHas('kecamatan', ['id_kecamatan' => $legacy]);
    }

    public function test_destroy_rejects_kecamatan_with_related_data(): void
    {
        $this->loginAs('admin');
        $related = [
            'desa' => fn (string $id) => Desa::factory()->create(['id_kecamatan' => $id]),
            'pic' => fn (string $id) => Picdlh::create(['id_kecamatan' => $id, 'nama' => 'Budi', 'kontak' => '081234567890', 'keterangan' => 'ketua']),
        ];

        foreach ($related as $label => $attach) {
            foreach ([Kecamatan::factory()->create()->id_kecamatan, $this->legacyKecamatan('lama-'.$label)] as $id) {
                $attach($id);
                $this->put('kecamatan/destroy', ['id_kecamatan' => $id])->assertOk()
                    ->assertJson(['success' => false, 'error' => 'Data gagal dihapus karena terkait dengan data lain']);
                $this->assertDatabaseHas('kecamatan', ['id_kecamatan' => $id]);
            }
        }
    }

    public function test_destroy_is_restricted_to_admin_and_pemda(): void
    {
        $kecamatan = Kecamatan::factory()->create();
        $legacy = $this->legacyKecamatan('kec-lama-03');

        $this->put('kecamatan/destroy', ['id_kecamatan' => $legacy])->assertRedirect(route('login'));

        $this->loginAs('kepala');
        $this->put('kecamatan/destroy', ['id_kecamatan' => $kecamatan->id_kecamatan])->assertForbidden();
        $this->put('kecamatan/destroy', ['id_kecamatan' => $legacy])->assertForbidden();

        foreach (['pt', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            $this->put('kecamatan/destroy', ['id_kecamatan' => $kecamatan->id_kecamatan])->assertRedirect(route('home'));
            $this->put('kecamatan/destroy', ['id_kecamatan' => $legacy])->assertRedirect(route('home'));
        }

        $this->assertDatabaseHas('kecamatan', ['id_kecamatan' => $kecamatan->id_kecamatan]);
        $this->assertDatabaseHas('kecamatan', ['id_kecamatan' => $legacy]);
    }
}
