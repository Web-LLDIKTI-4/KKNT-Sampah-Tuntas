<?php

namespace Tests\Feature\Master;

use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Picdlh;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PicDlhTest extends TestCase
{
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private const LIST_URL = 'picdlh/listdataserver?draw=1&start=0&length=10';

    private function makePic(array $attributes = []): Picdlh
    {
        return Picdlh::create($attributes + [
            'id_kecamatan' => Kecamatan::factory()->create()->id_kecamatan,
            'nama' => 'Budi',
            'kontak' => '081234567890',
            'keterangan' => 'ketua',
        ]);
    }

    private function payload(string $idKecamatan, array $override = []): array
    {
        return $override + [
            'id_kecamatan' => $idKecamatan,
            'nama' => 'Siti',
            'kontak' => '+62 812-3456-7890',
            'keterangan' => 'anggota',
        ];
    }

    public function test_viewer_roles_can_view_without_manage_controls(): void
    {
        $pic = $this->makePic();

        foreach (['dpl', 'pt', 'kepala'] as $role) {
            $this->loginAs($role);

            $this->get('picdlh')->assertOk()->assertDontSee('picdlh/tambah', false);
            $this->get('picdlh/listdata')->assertOk()->assertDontSee('Aksi')->assertDontSee("data: 'action'", false);
            $row = $this->getJson(self::LIST_URL, self::AJAX)->assertOk()->json('data.0');
            $this->assertSame('', $row['action'], $role);
            $this->assertSame('Ketua', $row['keterangan'], $role);

            $this->get('picdlh/tambah')->assertRedirect(route('home'));
            $this->get('picdlh/edit/'.$pic->id_pic_dlh)->assertRedirect(route('home'));
        }
    }

    public function test_admin_can_view_with_manage_controls(): void
    {
        $pic = $this->makePic();

        foreach (['admin'] as $role) {
            $this->loginAs($role);

            $this->get('picdlh')->assertOk()->assertSee('picdlh/tambah', false);
            $this->get('picdlh/listdata')->assertOk()->assertSee('Aksi')->assertSee("data: 'action'", false);
            $row = $this->getJson(self::LIST_URL, self::AJAX)->assertOk()->json('data.0');
            $this->assertStringContainsString($pic->id_pic_dlh, $row['action'], $role);

            $this->get('picdlh/tambah')->assertOk();
            $this->get('picdlh/edit/'.$pic->id_pic_dlh)->assertOk()
                ->assertSee('value="'.$pic->id_kecamatan.'" selected', false)
                ->assertSee('value="ketua" selected', false);
        }
    }

    public function test_mahasiswa_and_pemda_cannot_access(): void
    {
        $pic = $this->makePic();

        foreach (['mahasiswa', 'pemda'] as $role) {
            $this->loginAs($role);

            $this->get('picdlh')->assertRedirect(route('home'));
            $this->get('picdlh/listdata')->assertRedirect(route('home'));
            $this->getJson(self::LIST_URL, self::AJAX)->assertRedirect(route('home'));
            $this->get('picdlh/tambah')->assertRedirect(route('home'));
            $this->get('picdlh/edit/'.$pic->id_pic_dlh)->assertRedirect(route('home'));
        }
    }

    public function test_listdataserver_requires_ajax(): void
    {
        $this->loginAs('admin');

        $this->get(self::LIST_URL)->assertNotFound();
    }

    public function test_listdataserver_default_order_is_kecamatan_then_keterangan(): void
    {
        $this->loginAs('dpl');
        $kecA = Kecamatan::factory()->create(['kecamatan' => 'AAA Kecamatan']);
        $kecB = Kecamatan::factory()->create(['kecamatan' => 'BBB Kecamatan']);
        $this->makePic(['id_kecamatan' => $kecB->id_kecamatan, 'nama' => 'B1', 'keterangan' => 'ketua']);
        $this->makePic(['id_kecamatan' => $kecA->id_kecamatan, 'nama' => 'A3', 'keterangan' => 'lldikti']);
        $this->makePic(['id_kecamatan' => $kecA->id_kecamatan, 'nama' => 'A1', 'keterangan' => 'ketua']);
        $this->makePic(['id_kecamatan' => $kecA->id_kecamatan, 'nama' => 'A2', 'keterangan' => 'anggota']);
        $this->makePic(['id_kecamatan' => $kecA->id_kecamatan, 'nama' => 'A0', 'keterangan' => 'ketua']);

        // Request seperti DataTables default: order kolom 0 (No, orderable=false)
        $query = self::LIST_URL.'&order[0][column]=0&order[0][dir]=asc'
            .'&columns[0][data]=DT_RowIndex&columns[0][name]=DT_RowIndex&columns[0][orderable]=false&columns[0][searchable]=false'
            .'&columns[1][data]=kecamatan&columns[1][name]=kecamatan&columns[1][orderable]=true&columns[1][searchable]=true';
        $nama = collect($this->getJson($query, self::AJAX)->assertOk()->json('data'))->pluck('nama')->all();

        $this->assertSame(['A0', 'A1', 'A2', 'A3', 'B1'], $nama);
    }

    public function test_listdataserver_caps_page_length_at_100(): void
    {
        $this->loginAs('admin');
        $idKecamatan = Kecamatan::factory()->create()->id_kecamatan;
        for ($i = 0; $i < 105; $i++) {
            $this->makePic(['id_kecamatan' => $idKecamatan, 'nama' => 'PIC '.$i]);
        }

        foreach (['-1', '1000', '0'] as $length) {
            $this->getJson('picdlh/listdataserver?draw=1&start=0&length='.$length, self::AJAX)
                ->assertOk()
                ->assertJsonCount(100, 'data')
                ->assertJsonPath('recordsTotal', 105)
                ->assertJsonPath('recordsFiltered', 105);
        }

        // Missing start/length must not bypass paging
        $this->getJson('picdlh/listdataserver?draw=1', self::AJAX)->assertOk()->assertJsonCount(10, 'data');
    }

    public function test_listdataserver_malformed_order_does_not_500(): void
    {
        $this->loginAs('admin');
        $this->makePic(['id_kecamatan' => Kecamatan::factory()->create()->id_kecamatan]);

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
            'columns[-1][data]=nama&columns[-1][orderable]=true&order[0][column]=-1&order[0][dir]=asc',
            'columns[99999][data]=nama&columns[99999][orderable]=true&order[0][column]=99999',
            'columns[99999999999999999999][data]=nama&order[0][column]=99999999999999999999',
            'columns[7][data]=nama&columns[7][orderable]=true&columns[3][data]=nama&order[0][column]=7&order[0][dir]=asc',
            'columns[0][search][regex]=true&search[regex]=true',
            'columns[0][data]=nama%0A&columns[0][name]=nama%0A&columns[0][orderable]=true&order[0][column]=0',
            'searchPanes[nama][]=x',
            $many,
        ] as $order) {
            $this->getJson(self::LIST_URL.'&'.$order, self::AJAX)->assertOk()->assertJsonCount(1, 'data');
        }

        // 2500-term search is capped to 100 chars / 10 terms
        $start = microtime(true);
        $this->getJson(self::LIST_URL.'&columns[0][data]=nama&columns[0][searchable]=true&columns[0][search][value]='.str_repeat('a+', 2500).'&search[value]='.str_repeat('a+', 2500), self::AJAX)->assertOk();
        $this->assertLessThan(2, microtime(true) - $start);

        // regex=true is forced off: '[' becomes a LIKE match instead of a REGEXP error
        $this->getJson(self::LIST_URL.'&columns[0][data]=nama&columns[0][searchable]=true&columns[0][search][value]=%5B&columns[0][search][regex]=true', self::AJAX)
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_viewer_roles_cannot_write(): void
    {
        $pic = $this->makePic();

        foreach (['dpl', 'pt', 'kepala'] as $role) {
            $this->loginAs($role);

            $insert = $this->put('picdlh/insert', $this->payload($pic->id_kecamatan));
            $this->assertContains($insert->getStatusCode(), [302, 403], $role);

            $update = $this->put('picdlh/update', $this->payload($pic->id_kecamatan, ['id_pic_dlh' => $pic->id_pic_dlh, 'nama' => 'Diubah']));
            $this->assertContains($update->getStatusCode(), [302, 403], $role);

            $destroy = $this->put('picdlh/destroy', ['id_pic_dlh' => $pic->id_pic_dlh]);
            $this->assertContains($destroy->getStatusCode(), [302, 403], $role);
        }

        $this->assertSame(1, Picdlh::count());
        $this->assertSame('Budi', $pic->fresh()->nama);
    }

    public function test_mahasiswa_and_pemda_cannot_write(): void
    {
        $pic = $this->makePic();

        foreach (['mahasiswa', 'pemda'] as $role) {
            $this->loginAs($role);

            $insert = $this->put('picdlh/insert', $this->payload($pic->id_kecamatan));
            $this->assertContains($insert->getStatusCode(), [302, 403], $role);

            $update = $this->put('picdlh/update', $this->payload($pic->id_kecamatan, ['id_pic_dlh' => $pic->id_pic_dlh, 'nama' => 'Diubah']));
            $this->assertContains($update->getStatusCode(), [302, 403], $role);

            $destroy = $this->put('picdlh/destroy', ['id_pic_dlh' => $pic->id_pic_dlh]);
            $this->assertContains($destroy->getStatusCode(), [302, 403], $role);
        }

        $this->assertSame(1, Picdlh::count());
        $this->assertSame('Budi', $pic->fresh()->nama);
    }

    public function test_admin_can_insert_update_and_delete(): void
    {
        foreach (['admin'] as $role) {
            $this->loginAs($role);
            $kecamatan = Kecamatan::factory()->create();

            $this->put('picdlh/insert', $this->payload($kecamatan->id_kecamatan))
                ->assertJson(['success' => true]);
            $pic = Picdlh::where('id_kecamatan', $kecamatan->id_kecamatan)->firstOrFail();
            $this->assertSame('anggota', $pic->keterangan, $role);

            $this->put('picdlh/update', $this->payload($kecamatan->id_kecamatan, [
                'id_pic_dlh' => $pic->id_pic_dlh,
                'nama' => 'Nama Baru',
                'keterangan' => 'lldikti',
            ]))->assertJson(['success' => true]);
            $this->assertSame('Nama Baru', $pic->fresh()->nama, $role);
            $this->assertSame('lldikti', $pic->fresh()->keterangan, $role);

            $this->put('picdlh/destroy', ['id_pic_dlh' => $pic->id_pic_dlh])->assertJson(['success' => true]);
            $this->assertDatabaseMissing('pic_dlh', ['id_pic_dlh' => $pic->id_pic_dlh]);
        }
    }

    public function test_multiple_ketua_allowed_per_kecamatan(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        foreach (['Ketua 1', 'Ketua 2'] as $nama) {
            $this->put('picdlh/insert', $this->payload($kecamatan->id_kecamatan, ['nama' => $nama, 'keterangan' => 'ketua']))
                ->assertJson(['success' => true]);
        }

        $this->assertSame(2, Picdlh::where('keterangan', 'ketua')->count());
    }

    public function test_invalid_keterangan_is_rejected(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        foreach (['ketua_umum', 'KETUA', ''] as $keterangan) {
            $this->put('picdlh/insert', $this->payload($kecamatan->id_kecamatan, ['keterangan' => $keterangan]))
                ->assertJson(['success' => false])
                ->assertJsonValidationErrors('keterangan', 'errors');
        }

        $this->assertSame(0, Picdlh::count());
    }

    public function test_invalid_kontak_is_rejected(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        foreach (['08123abc', '---', '0812<script>', str_repeat('1', 51), '', "0812\n34567890", "0812\t34567890"] as $kontak) {
            $this->put('picdlh/insert', $this->payload($kecamatan->id_kecamatan, ['kontak' => $kontak]))
                ->assertJson(['success' => false])
                ->assertJsonValidationErrors('kontak', 'errors');
        }

        $this->assertSame(0, Picdlh::count());
    }

    public function test_invalid_kecamatan_and_missing_id_on_update_are_rejected(): void
    {
        $this->loginAs('admin');
        $pic = $this->makePic();

        $this->put('picdlh/insert', $this->payload('00000000-0000-0000-0000-000000000000'))
            ->assertJsonValidationErrors('id_kecamatan', 'errors');

        $this->put('picdlh/update', $this->payload($pic->id_kecamatan))
            ->assertJsonValidationErrors('id_pic_dlh', 'errors');

        $this->put('picdlh/destroy', ['id_pic_dlh' => ['x']])->assertNotFound();
        $this->assertSame(1, Picdlh::count());
    }

    public function test_kecamatan_with_pic_cannot_be_deleted(): void
    {
        $this->loginAs('admin');
        $pic = $this->makePic();

        $this->put('kecamatan/destroy', ['id_kecamatan' => $pic->id_kecamatan])
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('kecamatan', ['id_kecamatan' => $pic->id_kecamatan]);
        $this->assertDatabaseHas('pic_dlh', ['id_pic_dlh' => $pic->id_pic_dlh]);
    }

    public function test_admin_can_insert_and_update_without_location_program(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        $this->put('picdlh/insert', $this->payload($kecamatan->id_kecamatan))
            ->assertJson(['success' => true]);
        $pic = Picdlh::where('id_kecamatan', $kecamatan->id_kecamatan)->firstOrFail();
        $this->assertNull($pic->location_program);

        $this->put('picdlh/update', $this->payload($kecamatan->id_kecamatan, [
            'id_pic_dlh' => $pic->id_pic_dlh,
            'location_program' => '',
            'nama' => 'Nama Baru',
        ]))->assertJson(['success' => true]);
        $this->assertSame('Nama Baru', $pic->fresh()->nama);
        $this->assertNull($pic->fresh()->location_program);
    }

    public function test_admin_can_insert_and_update_with_location_program(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();
        [$lokasiA, $lokasiB] = LokasiProgram::factory()->count(2)->create();

        $this->put('picdlh/insert', $this->payload($kecamatan->id_kecamatan, ['location_program' => $lokasiA->id]))
            ->assertJson(['success' => true]);
        $pic = Picdlh::where('id_kecamatan', $kecamatan->id_kecamatan)->firstOrFail();
        $this->assertSame($lokasiA->id, $pic->location_program);
        $this->assertSame($lokasiA->nama_lokasi, $pic->lokasiProgram->nama_lokasi);

        $this->put('picdlh/update', $this->payload($kecamatan->id_kecamatan, [
            'id_pic_dlh' => $pic->id_pic_dlh,
            'location_program' => $lokasiB->id,
        ]))->assertJson(['success' => true]);
        $this->assertSame($lokasiB->id, $pic->fresh()->location_program);

        // Clearing the select resets the column to null
        $this->put('picdlh/update', $this->payload($kecamatan->id_kecamatan, [
            'id_pic_dlh' => $pic->id_pic_dlh,
            'location_program' => null,
        ]))->assertJson(['success' => true]);
        $this->assertNull($pic->fresh()->location_program);
    }

    public function test_invalid_location_program_is_rejected(): void
    {
        $this->loginAs('admin');
        $pic = $this->makePic();

        foreach (['bukan-uuid', '00000000-0000-0000-0000-000000000000', (string) \Illuminate\Support\Str::uuid(), ['x'], "' OR 1=1 --"] as $value) {
            $this->put('picdlh/insert', $this->payload($pic->id_kecamatan, ['location_program' => $value]))
                ->assertJson(['success' => false])
                ->assertJsonValidationErrors('location_program', 'errors');

            $this->put('picdlh/update', $this->payload($pic->id_kecamatan, ['id_pic_dlh' => $pic->id_pic_dlh, 'location_program' => $value]))
                ->assertJson(['success' => false])
                ->assertJsonValidationErrors('location_program', 'errors');
        }

        $this->assertSame(1, Picdlh::count());
        $this->assertNull($pic->fresh()->location_program);
    }

    public function test_tambah_and_edit_show_location_program_select(): void
    {
        $this->loginAs('admin');
        $lokasiA = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Uji A']);
        $lokasiB = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Uji <b>B</b>']);

        $this->get('picdlh/tambah')->assertOk()
            ->assertSee('Lokasi Program / Kecamatan')
            ->assertSee('name="location_program"', false)
            ->assertSee('value="'.$lokasiA->id.'"', false)
            ->assertSee('Kota Uji A')
            ->assertSee('Kota Uji &lt;b&gt;B&lt;/b&gt;', false)
            ->assertDontSee('Kota Uji <b>B</b>', false)
            ->assertDontSee('value="'.$lokasiA->id.'" selected', false);

        $pic = $this->makePic(['location_program' => $lokasiB->id]);
        $this->get('picdlh/edit/'.$pic->id_pic_dlh)->assertOk()
            ->assertSee('Lokasi Program / Kecamatan')
            ->assertSee('value="'.$lokasiB->id.'" selected', false)
            ->assertDontSee('value="'.$lokasiA->id.'" selected', false);

        // Null location_program: nothing preselected
        $plain = $this->makePic();
        $this->get('picdlh/edit/'.$plain->id_pic_dlh)->assertOk()
            ->assertSee('value="'.$lokasiA->id.'"', false)
            ->assertDontSee('value="'.$lokasiA->id.'" selected', false)
            ->assertDontSee('value="'.$lokasiB->id.'" selected', false);
    }

    public function test_listdataserver_returns_and_searches_lokasi_program_nama(): void
    {
        $this->loginAs('admin');
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kabupaten Zeta']);
        $this->makePic(['nama' => 'Dengan Lokasi', 'location_program' => $lokasi->id]);
        $this->makePic(['nama' => 'Tanpa Lokasi']);

        $rows = collect($this->getJson(self::LIST_URL, self::AJAX)->assertOk()->json('data'))->keyBy('nama');
        $this->assertSame('Kabupaten Zeta', $rows['Dengan Lokasi']['lokasi_program_nama']);
        $this->assertSame('', $rows['Tanpa Lokasi']['lokasi_program_nama']);

        $columns = '&columns[0][data]=nama&columns[0][name]=nama&columns[0][searchable]=true&columns[0][orderable]=true'
            .'&columns[1][data]=lokasi_program_nama&columns[1][name]=lokasi_program_nama&columns[1][searchable]=true&columns[1][orderable]=true';

        // Global search by lokasi name
        $this->getJson(self::LIST_URL.$columns.'&search[value]=Zeta', self::AJAX)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Dengan Lokasi')
            ->assertJsonPath('recordsFiltered', 1);

        // Column search by lokasi name
        $this->getJson(self::LIST_URL.$columns.'&columns[1][search][value]=Kabupaten', self::AJAX)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Dengan Lokasi');

        // Ordering by lokasi does not 500 (ambiguous column check)
        $this->getJson(self::LIST_URL.$columns.'&order[0][column]=1&order[0][dir]=desc', self::AJAX)
            ->assertOk()
            ->assertJsonPath('data.0.nama', 'Dengan Lokasi');
    }

    public function test_non_admin_cannot_set_location_program(): void
    {
        $pic = $this->makePic();
        $lokasi = LokasiProgram::factory()->create();

        foreach (['dpl', 'pt', 'kepala', 'mahasiswa', 'pemda'] as $role) {
            $this->loginAs($role);

            $insert = $this->put('picdlh/insert', $this->payload($pic->id_kecamatan, ['location_program' => $lokasi->id]));
            $this->assertContains($insert->getStatusCode(), [302, 403], $role);

            $update = $this->put('picdlh/update', $this->payload($pic->id_kecamatan, ['id_pic_dlh' => $pic->id_pic_dlh, 'location_program' => $lokasi->id]));
            $this->assertContains($update->getStatusCode(), [302, 403], $role);
        }

        $this->assertSame(1, Picdlh::count());
        $this->assertNull($pic->fresh()->location_program);
    }

    public function test_deleting_lokasi_program_nulls_pic_location_program(): void
    {
        $this->loginAs('admin');
        $lokasi = LokasiProgram::factory()->create();
        $pic = $this->makePic(['location_program' => $lokasi->id]);

        $this->put('lokasiprogram/destroy', ['id' => $lokasi->id])->assertJson(['success' => true]);

        $this->assertDatabaseMissing('lokasi_program', ['id' => $lokasi->id]);
        $this->assertDatabaseHas('pic_dlh', ['id_pic_dlh' => $pic->id_pic_dlh]);
        $this->assertNull($pic->fresh()->location_program);
    }

    public function test_admin_can_insert_without_kecamatan_stored_as_null(): void
    {
        $this->loginAs('admin');

        $this->put('picdlh/insert', $this->payload('', ['nama' => 'Kosong']))->assertJson(['success' => true]);

        $payload = $this->payload('', ['nama' => 'Tidak Dikirim']);
        unset($payload['id_kecamatan']);
        $this->put('picdlh/insert', $payload)->assertJson(['success' => true]);

        foreach (['Kosong', 'Tidak Dikirim'] as $nama) {
            $pic = Picdlh::where('nama', $nama)->firstOrFail();
            $this->assertNull($pic->id_kecamatan, $nama);
            $this->assertDatabaseHas('pic_dlh', ['id_pic_dlh' => $pic->id_pic_dlh, 'id_kecamatan' => null]);
        }
        $this->assertSame(0, Picdlh::where('id_kecamatan', '')->count());
    }

    public function test_admin_can_update_without_kecamatan_stored_as_null(): void
    {
        $this->loginAs('admin');
        $pic = $this->makePic(['id_kecamatan' => null]);

        $this->put('picdlh/update', $this->payload('', ['id_pic_dlh' => $pic->id_pic_dlh, 'nama' => 'Update Kosong']))
            ->assertJson(['success' => true]);
        $this->assertSame('Update Kosong', $pic->fresh()->nama);
        $this->assertNull($pic->fresh()->id_kecamatan);

        $payload = $this->payload('', ['id_pic_dlh' => $pic->id_pic_dlh, 'nama' => 'Update Tanpa Field']);
        unset($payload['id_kecamatan']);
        $this->put('picdlh/update', $payload)->assertJson(['success' => true]);
        $this->assertSame('Update Tanpa Field', $pic->fresh()->nama);
        $this->assertNull($pic->fresh()->id_kecamatan);
        $this->assertSame(0, Picdlh::where('id_kecamatan', '')->count());
    }

    public function test_clearing_kecamatan_on_edit_sets_null(): void
    {
        $this->loginAs('admin');
        $pic = $this->makePic();
        $this->assertNotNull($pic->id_kecamatan);

        $this->put('picdlh/update', $this->payload('', ['id_pic_dlh' => $pic->id_pic_dlh]))
            ->assertJson(['success' => true]);

        $this->assertNull($pic->fresh()->id_kecamatan);
        $this->assertDatabaseHas('pic_dlh', ['id_pic_dlh' => $pic->id_pic_dlh, 'id_kecamatan' => null]);

        // Setting it back to a valid kecamatan still works
        $kecamatan = Kecamatan::factory()->create();
        $this->put('picdlh/update', $this->payload($kecamatan->id_kecamatan, ['id_pic_dlh' => $pic->id_pic_dlh]))
            ->assertJson(['success' => true]);
        $this->assertSame($kecamatan->id_kecamatan, $pic->fresh()->id_kecamatan);
    }

    public function test_invalid_kecamatan_is_rejected_on_insert_and_update(): void
    {
        $this->loginAs('admin');
        $pic = $this->makePic();
        $original = $pic->id_kecamatan;

        foreach (['00000000-0000-0000-0000-000000000000', 'bukan-uuid', str_repeat('a', 37), ['x'], "' OR 1=1 --"] as $value) {
            $this->put('picdlh/insert', array_merge($this->payload(''), ['id_kecamatan' => $value]))
                ->assertJson(['success' => false])
                ->assertJsonValidationErrors('id_kecamatan', 'errors');

            $this->put('picdlh/update', array_merge($this->payload('', ['id_pic_dlh' => $pic->id_pic_dlh]), ['id_kecamatan' => $value]))
                ->assertJson(['success' => false])
                ->assertJsonValidationErrors('id_kecamatan', 'errors');
        }

        $this->assertSame(1, Picdlh::count());
        $this->assertSame($original, $pic->fresh()->id_kecamatan);
    }

    public function test_non_admin_cannot_insert_without_kecamatan(): void
    {
        foreach (['dpl', 'pt', 'kepala', 'mahasiswa', 'pemda'] as $role) {
            $this->loginAs($role);

            $insert = $this->put('picdlh/insert', $this->payload(''));
            $this->assertContains($insert->getStatusCode(), [302, 403], $role);
        }

        $this->assertSame(0, Picdlh::count());
    }

    public function test_listdataserver_returns_rows_without_kecamatan(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create(['kecamatan' => 'Kecamatan Alfa']);
        $this->makePic(['id_kecamatan' => $kecamatan->id_kecamatan, 'nama' => 'Dengan Kecamatan']);
        $this->makePic(['id_kecamatan' => null, 'nama' => 'Tanpa Kecamatan']);

        $response = $this->getJson(self::LIST_URL, self::AJAX)->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonCount(2, 'data');
        $rows = collect($response->json('data'))->keyBy('nama');
        $this->assertSame('Kecamatan Alfa', $rows['Dengan Kecamatan']['kecamatan']);
        $this->assertSame('', $rows['Tanpa Kecamatan']['kecamatan']);

        $columns = '&columns[0][data]=nama&columns[0][name]=nama&columns[0][searchable]=true&columns[0][orderable]=true'
            .'&columns[1][data]=kecamatan&columns[1][name]=kecamatan&columns[1][searchable]=true&columns[1][orderable]=true';

        foreach (['asc', 'desc'] as $dir) {
            $this->getJson(self::LIST_URL.$columns.'&order[0][column]=1&order[0][dir]='.$dir, self::AJAX)
                ->assertOk()->assertJsonCount(2, 'data');
        }

        $this->getJson(self::LIST_URL.$columns.'&search[value]=Tanpa', self::AJAX)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nama', 'Tanpa Kecamatan');

        $this->get('picdlh/listdata')->assertOk()
            ->assertSee("{data: 'kecamatan', name: 'kecamatan', defaultContent: '-', render: function (value) { return value || '-'; }}", false);
    }

    public function test_tambah_and_edit_kecamatan_select_is_optional(): void
    {
        $this->loginAs('admin');
        $kecamatan = Kecamatan::factory()->create();

        $this->get('picdlh/tambah')->assertOk()
            ->assertSee('<select name="id_kecamatan" class="form-control">', false)
            ->assertDontSee('<select name="id_kecamatan" class="form-control" required>', false)
            ->assertSeeInOrder(['name="id_kecamatan"', '<option value="">', 'value="'.$kecamatan->id_kecamatan.'"'], false);

        $pic = $this->makePic(['id_kecamatan' => $kecamatan->id_kecamatan]);
        $this->get('picdlh/edit/'.$pic->id_pic_dlh)->assertOk()
            ->assertSee('<select name="id_kecamatan" class="form-control">', false)
            ->assertDontSee('<select name="id_kecamatan" class="form-control" required>', false)
            ->assertSeeInOrder(['name="id_kecamatan"', '<option value="">', 'value="'.$kecamatan->id_kecamatan.'" selected'], false);

        // Null kecamatan: edit page renders and nothing is preselected
        $plain = $this->makePic(['id_kecamatan' => null]);
        $this->get('picdlh/edit/'.$plain->id_pic_dlh)->assertOk()
            ->assertSeeInOrder(['name="id_kecamatan"', '<option value="">', 'value="'.$kecamatan->id_kecamatan.'"'], false)
            ->assertDontSee('value="'.$kecamatan->id_kecamatan.'" selected', false);
    }
}
