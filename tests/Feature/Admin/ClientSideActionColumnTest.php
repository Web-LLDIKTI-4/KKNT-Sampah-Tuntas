<?php

namespace Tests\Feature\Admin;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

// Data DPL, Data Mahasiswa, Kelola Pengguna: action buttons rendered client-side (columns.actions), JSON carries only row keys
class ClientSideActionColumnTest extends TestCase
{
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    public function test_person_listdataserver_sends_keys_without_action_html(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create();
        $mahasiswa = Mahasiswa::factory()->create();

        foreach (['dpl' => ['id_dpl', $dpl->id_dpl], 'mahasiswa' => ['id_mahasiswa', $mahasiswa->id_mahasiswa]] as $prefix => [$key, $id]) {
            $row = $this->listRows($prefix.'/listdataserver')->firstWhere($key, $id);
            $this->assertNotNull($row, $prefix);
            $this->assertArrayNotHasKey('action', $row, $prefix);
            $this->assertArrayNotHasKey('nama_lokasi', $row, $prefix);
            $this->assertSame('Belum Terdata', $row['location_program'], $prefix);
        }
    }

    public function test_user_listdataserver_sends_id_role_akses_without_action_html(): void
    {
        $this->loginAs('admin');
        $ketua = User::factory()->role('mahasiswa')->create(['akses' => 'pjdesa']);

        $row = $this->listRows('user/listdataserver')->firstWhere('id', $ketua->id);
        $this->assertArrayNotHasKey('action', $row);
        $this->assertSame('mahasiswa', $row['role']);
        $this->assertSame('pjdesa', $row['akses']);
    }

    public function test_person_views_configure_edit_and_delete_actions(): void
    {
        $this->loginAs('admin');

        foreach (['dpl' => 'id_dpl', 'mahasiswa' => 'id_mahasiswa'] as $prefix => $key) {
            $this->assertSame(
                ['idField' => $key, 'edit' => url($prefix.'/edit').'/', 'delete' => url($prefix.'/destroy')],
                $this->actionConfig($prefix.'/listdata'),
                $prefix,
            );
        }
    }

    public function test_user_view_configures_edit_by_role_and_confirm_by_akses(): void
    {
        $this->loginAs('admin');
        $config = $this->actionConfig('user/listdata');

        $this->assertSame(url('user/edit').'/', $config['edit']);
        $this->assertSame('role', $config['editBy']['field']);
        $this->assertSame(url('user/edituserkepala').'/', $config['editBy']['map']['kepala']);
        $this->assertSame(url('user/edituserkepala').'/', $config['editBy']['map']['pemda']);
        $this->assertSame(url('user/edituserpt').'/', $config['editBy']['map']['pt']);
        $this->assertSame(url('user/destroy'), $config['delete']);
        $this->assertStringContainsString('SEMUA datanya', $config['confirm']);
        $this->assertSame('akses', $config['confirmBy']['field']);
        $this->assertStringContainsString('ketua kelompok', $config['confirmBy']['map']['pjdesa']);
    }

    public function test_configured_actions_still_work(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create();
        $mahasiswa = Mahasiswa::factory()->create();
        $user = User::factory()->role('mahasiswa')->create();

        foreach (['dpl' => $dpl->id_dpl, 'mahasiswa' => $mahasiswa->id_mahasiswa] as $prefix => $id) {
            $config = $this->actionConfig($prefix.'/listdata');
            $this->get($config['edit'].$id)->assertOk();
            $this->put($config['delete'], [$config['idField'] => $id])->assertJson(['success' => true]);
        }
        $this->assertDatabaseMissing('dpl', ['id_dpl' => $dpl->id_dpl]);
        $this->assertDatabaseMissing('mahasiswa', ['id_mahasiswa' => $mahasiswa->id_mahasiswa]);

        $config = $this->actionConfig('user/listdata');
        $this->get($config['edit'].$user->id)->assertOk();
        $this->put($config['delete'], ['id' => $user->id])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_non_admin_cannot_list_or_delete(): void
    {
        $dpl = Dpl::factory()->create();

        foreach (['pt', 'dpl', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            foreach (['dpl/listdataserver', 'mahasiswa/listdataserver', 'user/listdataserver'] as $uri) {
                $this->get($uri.'?draw=1&start=0&length=-1', self::AJAX)->assertRedirect(route('home'));
            }
            $this->put('dpl/destroy', ['id_dpl' => $dpl->id_dpl])->assertRedirect(route('home'));
        }
        $this->assertDatabaseHas('dpl', ['id_dpl' => $dpl->id_dpl]);
    }

    private function listRows(string $uri): Collection
    {
        return collect($this->getJson($uri.'?draw=1&start=0&length=-1', self::AJAX)->assertOk()->json('data'));
    }

    // columns.actions of the "action" column, as table-init.js reads it from data-columns
    private function actionConfig(string $uri): array
    {
        $html = $this->get($uri)->assertOk()->getContent();
        $this->assertSame(1, preg_match("/data-columns='([^']*)'/", $html, $m), $uri);
        $column = collect(json_decode($m[1], true))->firstWhere('data', 'action');
        $this->assertFalse($column['orderable'], $uri);

        return $column['actions'];
    }
}
