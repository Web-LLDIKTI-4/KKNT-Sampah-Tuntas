<?php

namespace Tests\Feature\Admin;

use App\Http\Requests\Admin\TableExportRequest;
use App\Http\Requests\Admin\UserExportRequest;
use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\ReadsXlsx;
use Tests\TestCase;

// Page length 10/25/50/100/500/All + export of all rows matching the table's view (DPL, Mahasiswa, Pengguna)
class TableViewExportTest extends TestCase
{
    use ReadsXlsx;
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    // Table columns as sent by DataTables (index matters for order[])
    private const COLUMNS = [
        'dpl' => ['DT_RowIndex', 'nidn', 'nama', 'email', 'phone', 'nm_lemb', 'location_program'],
        'mahasiswa' => ['DT_RowIndex', 'nim', 'nama', 'email', 'phone', 'nm_lemb', 'location_program', 'nama_kecamatan', 'nama_desa'],
        'user' => ['DT_RowIndex', 'name', 'email', 'id_number', 'pt_name', 'role'],
    ];

    // Email column index in exported data rows (No. column first)
    private const EMAIL_COL = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    // ---- listdataserver page length ----

    public function test_listdataserver_supports_500_and_all_on_three_tables(): void
    {
        $this->loginAs('admin');
        $this->seedRows(12);

        foreach (array_keys(self::COLUMNS) as $module) {
            foreach (['10' => 10, '25' => 12, '500' => 12, '-1' => 12, '7' => 7] as $length => $expected) {
                $response = $this->listServer($module, ['length' => $length])->assertOk();
                $this->assertCount($expected, $response->json('data'), "$module length=$length");
                $this->assertSame(12, $response->json('recordsFiltered'), "$module length=$length");
            }
        }
    }

    public function test_listdataserver_caps_oversized_length_at_500_and_all_returns_everything(): void
    {
        $this->loginAs('admin');
        $this->bulkDpl(501);
        $this->bulkUsers(501);

        foreach (['dpl', 'user'] as $module) {
            foreach (['500' => 500, '5000' => 500, '0' => 500, 'abc' => 10, '-1' => 501, '-5' => 500] as $length => $expected) {
                $response = $this->listServer($module, ['length' => $length])->assertOk();
                $this->assertCount($expected, $response->json('data'), "$module length=$length");
            }
        }
    }

    public function test_listdataserver_all_respects_filters(): void
    {
        $this->loginAs('admin');
        $this->seedRows(6);
        $this->mahasiswaWithFlags();
        User::factory()->role('pemda')->count(3)->create();

        $this->assertCount(1, $this->listServer('mahasiswa', ['length' => -1, 'ketua_kelompok' => '1'])->json('data'));
        $this->assertCount(3, $this->listServer('user', ['length' => -1, 'role' => 'pemda'])->json('data'));
        $this->assertCount(2, $this->listServer('dpl', ['length' => -1, 'search' => ['value' => 'Kembar']])->json('data'));
    }

    // ---- Export = all rows of the view (page/length ignored) ----

    public function test_export_ignores_length_and_start_and_returns_all_rows(): void
    {
        $this->loginAs('admin');
        $this->seedRows(25);

        foreach (array_keys(self::COLUMNS) as $module) {
            $all = $this->tableEmails($module, ['length' => -1]);
            $this->assertCount(25, $all, $module);

            // Legacy links may still carry start/length (even invalid ones); same order incl. tie-breaker
            foreach ([[], ['length' => 10], ['start' => 10, 'length' => 10], ['start' => 100, 'length' => 10], ['length' => 50], ['length' => -1], ['length' => 7], ['length' => 'abc'], ['start' => -1]] as $params) {
                $this->assertSame($all, $this->exportEmails($module, $params), "$module ".json_encode($params));
            }
        }
    }

    public function test_export_returns_all_filtered_rows_despite_length_10(): void
    {
        $this->loginAs('admin');
        $this->seedRows(25);

        foreach ([
            ['dpl', ['q' => 'Nama']],
            ['mahasiswa', ['q' => 'Nama']],
            ['mahasiswa', ['lokasi_status' => 'sudah']],
            ['user', ['q' => 'Nama']],
            ['user', ['role' => 'kepala', 'order_col' => 'email', 'order_dir' => 'desc']],
        ] as [$module, $params]) {
            $label = $module.' '.json_encode($params);
            $table = $this->tableEmails($module, $this->asTableParams($module, $params) + ['length' => -1]);
            $this->assertGreaterThan(10, count($table), $label);
            $this->assertSame($table, $this->exportEmails($module, $params + ['start' => 0, 'length' => 10]), $label);
        }
    }

    public function test_export_follows_search_and_order_of_table(): void
    {
        $this->loginAs('admin');
        $this->seedRows(15);

        $cases = [
            'dpl' => [['q' => 'Kembar'], ['order_col' => 'email', 'order_dir' => 'desc'], ['order_col' => 'nidn', 'order_dir' => 'asc'], ['order_col' => 'nm_lemb', 'order_dir' => 'desc']],
            'mahasiswa' => [['q' => 'Kembar'], ['order_col' => 'email', 'order_dir' => 'desc'], ['order_col' => 'nama_desa', 'order_dir' => 'desc'], ['order_col' => 'location_program', 'order_dir' => 'asc']],
            'user' => [['q' => 'Kembar'], ['order_col' => 'email', 'order_dir' => 'desc'], ['order_col' => 'role', 'order_dir' => 'asc'], ['order_col' => 'id_number', 'order_dir' => 'desc'], ['order_col' => 'pt_name', 'order_dir' => 'asc']],
        ];

        foreach ($cases as $module => $variants) {
            foreach ($variants as $params) {
                $label = $module.' '.json_encode($params);
                $table = $this->tableEmails($module, $this->asTableParams($module, $params) + ['length' => -1]);
                $this->assertSame($table, $this->exportEmails($module, $params + ['length' => -1]), $label);
                // Table paged -> export still all rows
                $this->assertSame($table, $this->exportEmails($module, $params + ['start' => 5, 'length' => 10]), $label.' paged');
            }
            $this->assertCount(2, $this->exportRows($module, ['q' => 'Kembar']), $module.' q');
        }
    }

    public function test_unknown_order_col_falls_back_to_default_order(): void
    {
        $this->loginAs('admin');
        $this->seedRows(8);

        foreach (array_keys(self::COLUMNS) as $module) {
            $default = $this->tableEmails($module, ['length' => -1]);
            foreach (['DT_RowIndex', 'action', 'password', 'nama; drop table users', 'u.id'] as $col) {
                $this->assertSame($default, $this->exportEmails($module, ['order_col' => $col, 'order_dir' => 'desc', 'length' => -1]), "$module order_col=$col");
            }
        }
    }

    public function test_mahasiswa_export_respects_lokasi_and_ketua_filters(): void
    {
        $this->loginAs('admin');
        $m = $this->mahasiswaWithFlags();

        $cases = [
            [['lokasi_status' => 'sudah'], ['ketuaSudah', 'biasaSudah']],
            [['lokasi_status' => 'belum'], ['biasaBelum']],
            [['ketua_kelompok' => '1'], ['ketuaSudah']],
            [['ketua_kelompok' => '0'], ['biasaBelum', 'biasaSudah']],
            [['lokasi_status' => 'sudah', 'ketua_kelompok' => '0'], ['biasaSudah']],
        ];
        foreach ($cases as [$filters, $keys]) {
            $expected = collect($keys)->map(fn ($k) => $m[$k]->email)->sort()->values()->all();
            $exported = $this->exportEmails('mahasiswa', $filters + ['length' => -1]);
            $this->assertSame($expected, collect($exported)->sort()->values()->all(), json_encode($filters));
            $this->assertSame($this->tableEmails('mahasiswa', $filters + ['length' => -1]), $exported, json_encode($filters).' vs table');
        }
    }

    public function test_user_export_respects_role_and_kodept_with_paging(): void
    {
        $this->loginAs('admin');
        $sp = Satuanpendidikan::factory()->create();
        Mahasiswa::factory()->count(3)->create(['kodept' => $sp->npsn])
            ->each(fn ($m) => User::factory()->role('mahasiswa')->create(['email' => $m->email, 'name' => $m->nama]));
        Mahasiswa::factory()->count(2)->create()
            ->each(fn ($m) => User::factory()->role('mahasiswa')->create(['email' => $m->email]));
        User::factory()->role('kepala')->count(4)->create();

        foreach ([
            [['kodept' => $sp->npsn], 3],
            [['role' => 'kepala'], 4],
            [['role' => 'mahasiswa'], 5],
            [['role' => 'mahasiswa', 'kodept' => $sp->npsn], 3],
        ] as [$filters, $count]) {
            $all = $this->exportEmails('user', $filters + ['length' => -1]);
            $this->assertCount($count, $all, json_encode($filters));
            $this->assertSame($this->tableEmails('user', $filters + ['length' => -1]), $all, json_encode($filters));
            $this->assertSame($all, $this->exportEmails('user', $filters + ['start' => 1, 'length' => 10]), json_encode($filters).' start=1');
        }
    }

    // ---- Validation ----

    public function test_invalid_export_params_are_rejected(): void
    {
        $this->loginAs('admin');
        $invalid = [
            ['order_dir' => 'up'],
            ['order_dir' => 'asc; drop'],
            ['q' => str_repeat('a', 101)],
            ['order_col' => str_repeat('a', 51)],
        ];

        foreach (array_keys(self::COLUMNS) as $module) {
            foreach ($invalid as $params) {
                $field = array_key_first($params);
                $this->get("$module/export?".http_build_query($params))->assertSessionHasErrors($field);
            }
        }
        foreach ([['lokasi_status' => 'x'], ['ketua_kelompok' => '2']] as $params) {
            $this->get('mahasiswa/export?'.http_build_query($params))->assertSessionHasErrors(array_key_first($params));
        }
        $this->get('user/export?role=admin')->assertSessionHasErrors('role');
        $this->get('user/export?kodept=00000000')->assertSessionHasErrors('kodept');
    }

    public function test_valid_lengths_accepted(): void
    {
        $this->loginAs('admin');
        $this->seedRows(2);

        foreach (array_keys(self::COLUMNS) as $module) {
            foreach ([10, 25, 50, 100, 500, -1] as $length) {
                $this->assertCount(2, $this->exportRows($module, ['length' => $length, 'order_dir' => 'desc']), "$module length=$length");
            }
        }
    }

    // ---- Authorization ----

    public function test_non_admin_cannot_export_table_view(): void
    {
        $this->seedRows(2);

        foreach (['pt', 'dpl', 'mahasiswa', 'kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            foreach (array_keys(self::COLUMNS) as $module) {
                $response = $this->get("$module/export?length=-1");
                $this->assertNotSame(200, $response->status(), "$role $module");
                $this->assertStringNotContainsString('spreadsheetml', (string) $response->headers->get('content-type'), "$role $module");
                if ($response->status() !== 403) {
                    $response->assertRedirect(route('home'));
                }
            }
            auth()->logout();
        }
    }

    public function test_guest_cannot_export_table_view(): void
    {
        foreach (['dpl.export', 'mahasiswa.export', 'user.export'] as $route) {
            $this->get(route($route, ['length' => -1]))->assertRedirect(route('login'));
        }
    }

    public function test_export_requests_authorize_admin_only(): void
    {
        foreach ([TableExportRequest::class, UserExportRequest::class] as $class) {
            foreach (['admin' => true, 'kepala' => false, 'pemda' => false, 'pt' => false, 'dpl' => false, 'mahasiswa' => false] as $role => $allowed) {
                $request = $class::create('x', 'GET');
                $request->setUserResolver(fn () => User::factory()->role($role)->make());
                $this->assertSame($allowed, $request->authorize(), "$class / $role");
            }
            $guest = $class::create('x', 'GET');
            $guest->setUserResolver(fn () => null);
            $this->assertFalse($guest->authorize(), "$class / guest");
        }
    }

    public function test_export_routes_are_rate_limited(): void
    {
        $this->withMiddleware(ThrottleRequests::class);

        // throttle:3,1 is keyed per user (shared across routes), so one admin per module
        foreach (['dpl', 'mahasiswa', 'user'] as $module) {
            $this->loginAs('admin');
            foreach (range(1, 3) as $i) {
                $this->get("$module/export?length=10")->assertOk();
            }
            $this->get("$module/export?length=10")->assertStatus(429);
        }
    }

    // ---- Regression: export-selected ----

    public function test_export_selected_still_follows_selection(): void
    {
        $this->loginAs('admin');
        $this->seedRows(3);
        $dpl = Dpl::orderBy('id_dpl')->get();
        $mhs = Mahasiswa::orderBy('id_mahasiswa')->get();
        $users = User::where('role', 'kepala')->orderBy('id')->get();

        foreach ([
            'dpl' => [[$dpl[2]->id_dpl, $dpl[0]->id_dpl], [$dpl[2]->email, $dpl[0]->email]],
            'mahasiswa' => [[$mhs[1]->id_mahasiswa, $mhs[2]->id_mahasiswa], [$mhs[1]->email, $mhs[2]->email]],
            'user' => [[$users[2]->id, $users[1]->id], [$users[2]->email, $users[1]->email]],
        ] as $module => [$ids, $emails]) {
            $ws = $this->xlsxFromResponse($this->post("$module/export-selected", ['ids' => $ids]));
            $this->assertSame($emails, $this->dataColumn($ws, self::EMAIL_COL), $module);
        }
    }

    // ---- UI: export button lives in index card-header, not in list partial ----

    public function test_export_button_rendered_once_on_index_and_not_in_list_partial(): void
    {
        $this->loginAs('admin');

        foreach ([
            'dpl' => ['dpl/listdata', '#dataTable'],
            'mahasiswa' => ['mahasiswa/listdata', '#dataTable'],
            'user' => ['user/listdata', '#dataTable-user'],
        ] as $module => [$partial, $table]) {
            $html = $this->get($module)->assertOk()->getContent();
            $href = preg_quote(route("$module.export"), '/');

            $this->assertSame(1, substr_count($html, 'data-export-table='), $module);
            $this->assertMatchesRegularExpression('/<a\b[^>]*href="'.$href.'"[^>]*>/', $html, $module);
            preg_match('/<a\b[^>]*data-export-table="[^"]*"[^>]*>/', $html, $tag);
            $this->assertStringContainsString('href="'.route("$module.export").'"', $tag[0], $module);
            $this->assertStringContainsString('data-export-table="'.$table.'"', $tag[0], $module);
            $this->assertStringContainsString('data-export-state', $tag[0], $module);

            $partialHtml = $this->get($partial)->assertOk()->getContent();
            $this->assertStringNotContainsString('data-export-table', $partialHtml, $partial);
            $this->assertStringNotContainsString('href="'.route("$module.export"), $partialHtml, $partial);
        }
    }

    public function test_non_admin_cannot_open_export_index_pages(): void
    {
        $this->loginAs('mahasiswa');

        foreach (['dpl', 'mahasiswa', 'user'] as $module) {
            $this->get($module)->assertRedirect(route('home'));
        }
    }

    // ---- Helpers ----

    // n DPL, n mahasiswa, n kepala users; two rows per module named "Kembar" (duplicate names, tie-breaker)
    private function seedRows(int $n): void
    {
        $sps = Satuanpendidikan::factory()->count(3)->create();
        foreach (range(1, $n) as $i) {
            $name = $i <= 2 ? 'Kembar Sama' : 'Nama '.($i % 4);
            Dpl::factory()->create(['nama' => $name, 'kodept' => $sps[$i % 3]->npsn]);
            $m = Mahasiswa::factory()->create(['nama' => $name, 'kodept' => $sps[$i % 3]->npsn]);
            if ($i % 2 === 0) {
                Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $m->id_mahasiswa, 'id_desa' => Desa::factory()->create()->id_desa]);
            }
            User::factory()->role('kepala')->create(['name' => $name]);
        }
    }

    private function mahasiswaWithFlags(): array
    {
        $desa = fn () => Desa::factory()->create()->id_desa;
        $m = [];
        foreach (['ketuaSudah' => [true, true], 'biasaSudah' => [false, true], 'biasaBelum' => [false, false]] as $key => [$ketua, $lokasi]) {
            $m[$key] = Mahasiswa::factory()->create();
            if ($lokasi) {
                Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $m[$key]->id_mahasiswa, 'id_desa' => $desa()]);
            }
            if ($ketua) {
                Pjdesa::create(['email' => $m[$key]->email, 'id_desa' => $desa()]);
            }
        }

        return $m;
    }

    private function bulkDpl(int $n): void
    {
        $rows = Dpl::factory()->count($n)->make()->map(fn ($d) => $d->getAttributes() + ['id_dpl' => (string) Str::uuid()])->all();
        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('dpl')->insert($chunk);
        }
    }

    private function bulkUsers(int $n): void
    {
        $now = now();
        $rows = User::factory()->role('kepala')->count($n)->make()
            ->map(fn ($u) => $u->getAttributes() + ['id' => (string) Str::uuid(), 'created_at' => $now, 'updated_at' => $now])->all();
        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('users')->insert($chunk);
        }
    }

    // Export params -> equivalent DataTables params
    private function asTableParams(string $module, array $params): array
    {
        $out = array_diff_key($params, array_flip(['q', 'order_col', 'order_dir']));
        if (isset($params['q'])) {
            $out['search'] = ['value' => $params['q']];
        }
        if (isset($params['order_col'])) {
            $out['order'] = [['column' => array_search($params['order_col'], self::COLUMNS[$module], true), 'dir' => $params['order_dir'] ?? 'asc']];
        }

        return $out;
    }

    private function listServer(string $module, array $params = []): TestResponse
    {
        $columns = array_map(fn ($c) => [
            'data' => $c, 'name' => $c, 'searchable' => $c === 'DT_RowIndex' ? 'false' : 'true', 'orderable' => $c === 'DT_RowIndex' ? 'false' : 'true',
        ], self::COLUMNS[$module]);

        return $this->getJson("$module/listdataserver?".http_build_query($params + [
            'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $columns,
        ]), self::AJAX);
    }

    private function tableEmails(string $module, array $params): array
    {
        $response = $this->listServer($module, $params)->assertOk();

        return array_column($response->json('data'), 'email');
    }

    private function exportRows(string $module, array $params = []): array
    {
        return $this->dataRows($this->xlsxFromResponse($this->get("$module/export?".http_build_query($params)), "/filename=.+\\.xlsx/"));
    }

    private function exportEmails(string $module, array $params = []): array
    {
        return array_column($this->exportRows($module, $params), self::EMAIL_COL);
    }
}
