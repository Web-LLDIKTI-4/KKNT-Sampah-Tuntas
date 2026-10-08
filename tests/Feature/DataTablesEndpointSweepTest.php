<?php

namespace Tests\Feature;

use App\Models\Dplmentoring;
use App\Models\Mahasiswa;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteItem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Sweep semua endpoint DataTables server-side: columns[] diambil langsung dari view,
 * role dari middleware route, data dari DatabaseSeeder. Variasi: order default (kolom 0 asc),
 * order tiap kolom orderable (asc & desc), global search. Wajib 200 tanpa key "error".
 */
class DataTablesEndpointSweepTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES = ['admin', 'kepala', 'pemda', 'dpl', 'pt', 'mahasiswa'];

    private const SEARCH = 'a';

    // Parse semua view server-side → [view, routeName|uri, columns]
    public static function endpoints(): array
    {
        $hasil = [];
        // Diagnostik: SWEEP_VIEW_REF=HEAD membaca view versi git tertentu
        $ref = getenv('SWEEP_VIEW_REF') ?: null;
        foreach (File::allFiles(resource_path('views')) as $file) {
            $src = $file->getContents();
            if ($ref) {
                $rel = 'resources/views/'.str_replace(resource_path('views').'/', '', $file->getPathname());
                $src = (string) shell_exec('git -C '.escapeshellarg(base_path()).' show '.escapeshellarg($ref.':'.$rel).' 2>/dev/null') ?: $src;
            }
            if (! preg_match('/serverSide\s*:\s*true/', $src)) {
                continue;
            }
            if (! preg_match('/ajax\s*:\s*"\{\{\s*(route|url)\(\s*\'([^\']+)\'/', $src, $m)) {
                continue;
            }
            $view = str_replace(resource_path('views').'/', '', $file->getPathname());
            $hasil[$view] = ['view' => $view, 'type' => $m[1], 'target' => $m[2], 'columns' => self::columns($src)];
        }
        ksort($hasil);

        return $hasil;
    }

    // Ambil objek kolom top-level dari `columns: [ ... ]`
    private static function columns(string $src): array
    {
        // Buang komentar JS/Blade agar kolom yang di-comment tidak ikut
        $src = preg_replace(['/\{\{--.*?--\}\}/s', '/\/\*.*?\*\//s', '/(?<![:\'"])\/\/[^\n]*/'], '', $src);
        if (! preg_match('/columns\s*:\s*\[/', $src, $m, PREG_OFFSET_CAPTURE)) {
            // Pola `var columns = [...]; ... columns: columns`
            if (! preg_match('/columns\s*:\s*(\w+)/', $src, $v) || ! preg_match('/\b'.$v[1].'\s*=\s*\[/', $src, $m, PREG_OFFSET_CAPTURE)) {
                return [];
            }
        }
        $start = strpos($src, '[', $m[0][1]);
        $depth = 0;
        $objek = [];
        $buf = '';
        for ($i = $start + 1, $n = strlen($src); $i < $n; $i++) {
            $c = $src[$i];
            if ($depth === 0 && $c === ']') {
                break;
            }
            if ($c === '{') {
                $depth++;
            }
            if ($depth > 0) {
                $buf .= $c;
            }
            if ($c === '}') {
                $depth--;
                if ($depth === 0) {
                    $objek[] = $buf;
                    $buf = '';
                }
            }
        }

        return array_map(function (string $o) {
            $prop = fn (string $key) => preg_match('/\b'.$key.'\s*:\s*(?:[\'"]([^\'"]*)[\'"]|(true|false|null))/', $o, $m)
                ? ($m[1] !== '' ? $m[1] : ($m[2] ?? null)) : null;
            $data = $prop('data');

            return [
                'data' => $data === 'null' ? '' : (string) $data,
                'name' => (string) ($prop('name') ?? ($data === 'null' ? '' : $data)),
                'orderable' => $prop('orderable') !== 'false',
                'searchable' => $prop('searchable') !== 'false',
            ];
        }, $objek);
    }

    private function query(array $columns, array $extra): string
    {
        $cols = [];
        foreach ($columns as $i => $c) {
            $cols[$i] = [
                'data' => $c['data'], 'name' => $c['name'],
                'searchable' => $c['searchable'] ? 'true' : 'false',
                'orderable' => $c['orderable'] ? 'true' : 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ];
        }

        return http_build_query($extra + [
            'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $cols,
            'order' => [['column' => 0, 'dir' => 'asc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ]);
    }

    // View hanya dirender untuk role tertentu walau route terbuka untuk role lain (by design 404)
    private const VIEW_ROLES = [
        'pendataanpemilahan/listdata.blade.php' => ['mahasiswa'],
    ];

    private function roles(RouteItem $route): array
    {
        foreach ($route->gatherMiddleware() as $mw) {
            if (is_string($mw) && str_starts_with($mw, 'role:')) {
                return array_values(array_intersect(self::ROLES, explode(',', substr($mw, 5))));
            }
        }

        return [];
    }

    public function test_semua_endpoint_datatables_tanpa_error(): void
    {
        Storage::fake('local');
        // Diagnostik kondisi sebelum fix DT_RowIndex: SWEEP_TANPA_FIX=1 php artisan test --filter=Sweep
        if (getenv('SWEEP_TANPA_FIX')) {
            config(['datatables.columns.blacklist.dt_row_index' => null]);
        }
        $this->seed(DatabaseSeeder::class);

        // Satu mahasiswa kelompok + DPL & PT-nya agar {email} terlihat oleh tiap role
        $mhs = Mahasiswa::where('email', 'like', 'ketua.k1.pt1.%')->firstOrFail();
        $akun = [
            'admin' => User::where('role', 'admin')->first(),
            'kepala' => User::where('role', 'kepala')->first(),
            'pemda' => User::where('role', 'pemda')->first(),
            'dpl' => User::where('email', Dplmentoring::where('email_mahasiswa', $mhs->email)->value('email_dpl'))->first(),
            'pt' => User::where('email', $mhs->kodept)->first(),
            'mahasiswa' => User::where('email', $mhs->email)->first(),
        ];
        foreach ($akun as $role => $u) {
            $this->assertNotNull($u, "akun $role dari seeder");
        }

        $gagal = [];
        $skip = [];
        $jumlah = 0;
        foreach (self::endpoints() as $ep) {
            try {
                $route = $ep['type'] === 'route' ? Route::getRoutes()->getByName($ep['target']) : Route::getRoutes()->match(request()->create($ep['target']));
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException) {
                $route = null;
            }
            if (! $route) {
                $skip[] = "{$ep['view']}: route '{$ep['target']}' tidak ada (view mati)";

                continue;
            }
            if ($ep['columns'] === []) {
                $skip[] = "{$ep['view']}: columns tidak bisa di-parse";

                continue;
            }
            $butuhEmail = in_array('email', $route->parameterNames(), true);
            $uri = $butuhEmail ? str_replace('{email}', rawurlencode($mhs->email), $route->uri()) : $route->uri();

            $variasi = ['order default (kolom 0 asc)' => []];
            foreach ($ep['columns'] as $i => $c) {
                if ($c['orderable']) {
                    foreach (['asc', 'desc'] as $dir) {
                        $variasi["order {$c['data']} $dir"] = ['order' => [['column' => $i, 'dir' => $dir]]];
                    }
                }
            }
            $variasi['search "'.self::SEARCH.'"'] = ['search' => ['value' => self::SEARCH, 'regex' => 'false']];

            $roles = isset(self::VIEW_ROLES[$ep['view']]) ? array_intersect($this->roles($route), self::VIEW_ROLES[$ep['view']]) : $this->roles($route);
            foreach ($roles as $role) {
                // Mahasiswa sengaja 404 untuk detail milik {email} (hanya reviewer)
                if ($butuhEmail && $role === 'mahasiswa') {
                    continue;
                }
                $this->actingAs($akun[$role]);
                foreach ($variasi as $label => $extra) {
                    $jumlah++;
                    $res = $this->getJson('/'.$uri.'?'.$this->query($ep['columns'], $extra), ['X-Requested-With' => 'XMLHttpRequest']);
                    $error = $res->status() === 200 ? $res->json('error') : null;
                    if ($res->status() !== 200 || $error !== null) {
                        $pesan = $error ?? ($res->json('message') ?? '');
                        preg_match('/SQLSTATE\[[^\]]+\]:[^(]+/', (string) $pesan, $sql);
                        $gagal["{$route->uri()} | $label | ".($sql[0] ?? substr((string) $pesan, 0, 120))][] = "$role:{$res->status()}";
                    }
                }
            }
        }

        $laporan = collect($gagal)->map(fn ($roles, $k) => $k.' | '.implode(',', array_unique($roles)))->values()->all();
        fwrite(STDERR, "\nSWEEP: $jumlah request, ".count($laporan)." kasus gagal\n".implode("\n", $laporan)."\nSKIP:\n".implode("\n", $skip)."\n");

        $this->assertSame([], $laporan, "Endpoint DataTables gagal:\n".implode("\n", $laporan));
    }
}
