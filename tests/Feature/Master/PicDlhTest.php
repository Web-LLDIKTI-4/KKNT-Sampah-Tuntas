<?php

namespace Tests\Feature\Master;

use App\Models\Kecamatan;
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
}
