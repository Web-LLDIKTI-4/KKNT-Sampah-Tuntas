<?php

namespace Tests\Feature\Admin;

use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\Kecamatan;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InspectsSortableTables;
use Tests\TestCase;

// Kolom status lokasi (kecamatan/desa) + filter lokasi_status di Data Mahasiswa admin, PT, dan mentoring DPL
class LokasiStatusColumnTest extends TestCase
{
    use InspectsSortableTables;
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private const XSS = '<img src=x onerror=alert(1)>';

    // Columns as the browser sends them, parsed from the rendered x-table
    private function viewColumns(string $listdataUrl): array
    {
        $xpath = $this->xpathOf($this->get($listdataUrl)->assertOk()->getContent());
        $table = $this->findTable($xpath, '//table[@data-datatable]');

        return json_decode($table->getAttribute('data-columns'), true);
    }

    private function dtParams(array $columns, array $extra = []): array
    {
        $cols = [];
        foreach ($columns as $c) {
            $cols[] = [
                'data' => $c['data'],
                'name' => $c['name'] ?? $c['data'],
                'searchable' => ($c['searchable'] ?? true) ? 'true' : 'false',
                'orderable' => ($c['orderable'] ?? true) ? 'true' : 'false',
                'search' => ['value' => $c['search'] ?? '', 'regex' => 'false'],
            ];
        }

        return $extra + ['draw' => 1, 'start' => 0, 'length' => 100, 'columns' => $cols, 'search' => ['value' => '', 'regex' => 'false']];
    }

    private function fetch(string $endpoint, array $params): TestResponse
    {
        $response = $this->getJson($endpoint.'?'.http_build_query($params), self::AJAX)->assertOk();
        $this->assertArrayNotHasKey('error', $response->json(), (string) $response->json('error'));

        return $response;
    }

    private function columnIndex(array $columns, string $data): int
    {
        $index = array_search($data, array_column($columns, 'data'), true);
        $this->assertIsInt($index, "Kolom {$data} harus ada di view");

        return $index;
    }

    private function lokasi(Mahasiswa $mhs, ?Desa $desa, int $tahun, array $attrs = []): Mahasiswa_lokasi
    {
        return Mahasiswa_lokasi::create($attrs + ['tahun' => $tahun, 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $desa?->id_desa]);
    }

    private function desa(string $desa, ?string $kecamatan): Desa
    {
        return Desa::factory()->create([
            'desa' => $desa,
            'id_kecamatan' => $kecamatan === null ? null : Kecamatan::factory()->create(['kecamatan' => $kecamatan])->id_kecamatan,
        ]);
    }

    private function mahasiswaRows(array $extra = [], array $columnSearch = []): TestResponse
    {
        $columns = $this->viewColumns('mahasiswa/listdata');
        foreach ($columnSearch as $data => $value) {
            $columns[$this->columnIndex($columns, $data)]['search'] = $value;
        }

        return $this->fetch('mahasiswa/listdataserver', $this->dtParams($columns, $extra));
    }

    public function test_mahasiswa_shows_desa_and_kecamatan_from_latest_row(): void
    {
        $this->loginAs('admin');
        $y = (int) date('Y');
        $t0 = Carbon::parse('2026-01-01 08:00:00');

        // Tahun terbaru menang; baris tahun lebih baru tanpa desa diabaikan
        $multiYear = Mahasiswa::factory()->create();
        $this->lokasi($multiYear, $this->desa('Desa Tua', 'Kec Tua'), $y - 2);
        $this->lokasi($multiYear, $this->desa('Desa Baru', 'Kec Baru'), $y - 1);
        $this->lokasi($multiYear, null, $y);

        // Dobel di tahun yang sama: updated_at terbaru menang (dibuat lebih dulu)
        $dupUpdated = Mahasiswa::factory()->create();
        $this->lokasi($dupUpdated, $this->desa('Desa Zulu Update', 'Kec Alfa Update'), $y, ['updated_at' => $t0->copy()->addDay()]);
        $this->lokasi($dupUpdated, $this->desa('Desa Alfa Update', 'Kec Zulu Update'), $y, ['updated_at' => $t0]);

        // Dobel tahun & updated_at sama: id_lokasi terbesar menang; urutan nama desa/kecamatan sengaja berlawanan
        $dupId = Mahasiswa::factory()->create();
        $this->lokasi($dupId, $this->desa('Desa Alfa Id', 'Kec Zulu Id'), $y, ['id_lokasi' => 'ffffffff-ffff-4fff-bfff-ffffffffffff', 'updated_at' => $t0]);
        $this->lokasi($dupId, $this->desa('Desa Zulu Id', 'Kec Alfa Id'), $y, ['id_lokasi' => '00000000-0000-4000-8000-000000000000', 'updated_at' => $t0]);

        $tanpaKecamatan = Mahasiswa::factory()->create();
        $this->lokasi($tanpaKecamatan, $this->desa('Desa Yatim', null), $y);

        $rows = collect($this->mahasiswaRows()->json('data'))->keyBy('email');

        $expected = [
            $multiYear->email => ['Kec Baru', 'Desa Baru'],
            $dupUpdated->email => ['Kec Alfa Update', 'Desa Zulu Update'],
            $dupId->email => ['Kec Zulu Id', 'Desa Alfa Id'],
            $tanpaKecamatan->email => [null, 'Desa Yatim'],
        ];
        foreach ($expected as $email => [$kecamatan, $desa]) {
            $this->assertSame($kecamatan, $rows[$email]['nama_kecamatan'], "nama_kecamatan {$email}");
            $this->assertSame($desa, $rows[$email]['nama_desa'], "nama_desa {$email}");
        }
        // Tidak ada baris dobel akibat lokasi dobel
        $this->assertCount(4, $rows);
    }

    public function test_mahasiswa_without_lokasi_or_only_null_desa_gets_null(): void
    {
        $this->loginAs('admin');
        $none = Mahasiswa::factory()->create();
        $nullDesa = Mahasiswa::factory()->create();
        $this->lokasi($nullDesa, null, (int) date('Y'));
        $this->lokasi($nullDesa, null, (int) date('Y') - 1);

        $rows = collect($this->mahasiswaRows()->json('data'))->keyBy('email');

        foreach ([$none->email, $nullDesa->email] as $email) {
            foreach (['nama_kecamatan', 'nama_desa'] as $key) {
                $this->assertArrayHasKey($key, $rows[$email]);
                $this->assertNull($rows[$email][$key], "{$key} {$email}");
            }
        }
    }

    public function test_mahasiswa_lokasi_columns_sort_asc_and_desc(): void
    {
        $this->loginAs('admin');
        $y = (int) date('Y');
        // Urutan nama mahasiswa berlawanan dengan urutan desa/kecamatan agar default order nama ketahuan
        foreach ([['Zaenal', 'Desa Alfa', 'Kec Charlie'], ['Yanti', 'Desa Bravo', 'Kec Bravo'], ['Xavier', 'Desa Charlie', 'Kec Alfa']] as [$nama, $desa, $kec]) {
            $this->lokasi(Mahasiswa::factory()->create(['nama' => $nama]), $this->desa($desa, $kec), $y);
        }
        $columns = $this->viewColumns('mahasiswa/listdata');

        $cases = [
            'nama_desa' => ['Desa Alfa', 'Desa Bravo', 'Desa Charlie'],
        ];
        foreach ($cases as $data => $asc) {
            $index = $this->columnIndex($columns, $data);
            $this->assertTrue($columns[$index]['orderable'] ?? true, "{$data} harus orderable");
            foreach (['asc' => $asc, 'desc' => array_reverse($asc)] as $dir => $expected) {
                $got = $this->fetch('mahasiswa/listdataserver', $this->dtParams($columns, ['order' => [['column' => $index, 'dir' => $dir]]]))->json('data.*.'.$data);
                $this->assertSame($expected, $got, "order {$data} {$dir}");
            }
        }

        // Status Set Lokasi (nama_kecamatan) sengaja tidak orderable; order manual tetap tanpa error
        $index = $this->columnIndex($columns, 'nama_kecamatan');
        $this->assertFalse($columns[$index]['orderable'], 'nama_kecamatan tidak orderable');
        foreach (['asc', 'desc'] as $dir) {
            $this->assertCount(3, $this->fetch('mahasiswa/listdataserver', $this->dtParams($columns, ['order' => [['column' => $index, 'dir' => $dir]]]))->json('data'));
        }
    }

    public function test_mahasiswa_column_and_global_search_match_latest_lokasi_only(): void
    {
        $this->loginAs('admin');
        $y = (int) date('Y');
        $target = Mahasiswa::factory()->create();
        $this->lokasi($target, $this->desa('Desa Qxlama', 'Kec Qklama'), $y - 1);
        $this->lokasi($target, $this->desa('Desa Qxbaru', 'Kec Qkbaru'), $y);
        $other = Mahasiswa::factory()->create();
        $this->lokasi($other, $this->desa('Desa Lain', 'Kec Lain'), $y);
        Mahasiswa::factory()->create();

        $emails = fn (TestResponse $r) => collect($r->json('data'))->pluck('email')->all();

        foreach (['nama_desa' => ['Qxbaru', 'Qxlama'], 'nama_kecamatan' => ['Qkbaru', 'Qklama']] as $data => [$latest, $old]) {
            $this->assertSame([$target->email], $emails($this->mahasiswaRows([], [$data => $latest])), "column search {$data} terbaru");
            $this->assertSame([], $emails($this->mahasiswaRows([], [$data => $old])), "column search {$data} lama tidak boleh cocok");

            $this->assertSame([$target->email], $emails($this->mahasiswaRows(['search' => ['value' => $latest, 'regex' => 'false']])), "global search {$latest}");
            $this->assertSame([], $emails($this->mahasiswaRows(['search' => ['value' => $old, 'regex' => 'false']])), "global search {$old} lama tidak boleh cocok");
        }

        // Wildcard & payload SQL diperlakukan literal, tanpa error
        foreach (['%', '_', "' OR 1=1 -- ", '") OR ("1"="1'] as $payload) {
            $r = $this->mahasiswaRows([], ['nama_desa' => $payload]);
            $this->assertSame(0, $r->json('recordsFiltered'), "column search literal: {$payload}");
            $this->mahasiswaRows(['search' => ['value' => $payload, 'regex' => 'false']]);
        }
    }

    public function test_mahasiswa_lokasi_name_is_escaped_in_json(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create();
        $this->lokasi($mhs, $this->desa(self::XSS, '<script>alert(1)</script>'), (int) date('Y'));

        $response = $this->mahasiswaRows();

        $this->assertSame(e(self::XSS), $response->json('data.0.nama_desa'));
        $this->assertSame(e('<script>alert(1)</script>'), $response->json('data.0.nama_kecamatan'));
        $this->assertStringNotContainsString('<img', $response->getContent());
        $this->assertStringNotContainsString('<script', $response->getContent());
    }

    public function test_listdata_views_keep_header_column_contract(): void
    {
        $this->loginAs('admin');

        $cases = [
            'mahasiswa/listdata' => ['nama_kecamatan' => 'Status Set Lokasi', 'nama_desa' => 'Kelurahan/Desa'],
            'dpl/listdata' => [],
        ];
        foreach ($cases as $url => $newColumns) {
            $xpath = $this->xpathOf($this->get($url)->assertOk()->getContent());
            $table = $this->findTable($xpath, '//table[@data-datatable]');
            $columns = json_decode($table->getAttribute('data-columns'), true);
            $ths = iterator_to_array($xpath->query('./thead/tr/th', $table));

            $this->assertCount(count($columns), $ths, "{$url}: jumlah th == columns");
            $this->assertSame('action', end($columns)['data'], "{$url}: Aksi kolom terakhir");
            $this->assertSame('Aksi', trim(end($ths)->textContent));
            foreach ($newColumns as $data => $label) {
                $i = $this->columnIndex($columns, $data);
                $this->assertSame($label, trim($ths[$i]->textContent), "{$url}: header {$data}");
            }
            if ($url === 'mahasiswa/listdata') {
                $this->assertFalse($table->hasAttribute('data-check'));
                $this->assertSame('ketua_kelompok', $columns[7]['data']);
                $this->assertSame(['Ketua kelompok', 'Bukan ketua'], $columns[7]['boolIcon']);
                $this->assertSame('Status Ketua Kelompok', trim($ths[7]->textContent));
            } else {
                // Data DPL kembali ke kolom semula
                $this->assertSame(['DT_RowIndex', 'nidn', 'nama', 'email', 'phone', 'nm_lemb', 'location_program', 'action'], array_column($columns, 'data'));
                $this->assertFalse($table->hasAttribute('data-check'));
            }
        }

        // emptyBadge hanya di tabel mahasiswa (admin, PT, mentoring); ratioOf (kolom DPL) sudah dihapus total
        $emptyBadgeViews = ['mahasiswa/listdata.blade.php', 'mahasiswa/pt/listdata.blade.php', 'mentoring/listdata.blade.php'];
        foreach (File::allFiles(resource_path('views')) as $file) {
            $rel = str_replace(resource_path('views').'/', '', $file->getPathname());
            $this->assertStringNotContainsString('ratioOf', $file->getContents(), $rel);
            if (! in_array($rel, $emptyBadgeViews, true)) {
                $this->assertStringNotContainsString('emptyBadge', $file->getContents(), $rel);
            }
        }
        foreach (['table-init.js', 'datatables-global.js'] as $js) {
            $this->assertDoesNotMatchRegularExpression('/ratioOf|renderRatio/', File::get(public_path('js/'.$js)), $js);
        }

        // dpl/listdata identik dengan HEAD (kolom bimbingan sudah di-revert)
        $head = (string) shell_exec('git -C '.escapeshellarg(base_path()).' show HEAD:resources/views/dpl/listdata.blade.php 2>/dev/null');
        if ($head !== '') {
            $this->assertSame($head, File::get(resource_path('views/dpl/listdata.blade.php')), 'dpl/listdata harus sama dengan HEAD');
        }

        // JSON Data DPL tidak membawa kolom lokasi/bimbingan
        Dpl::factory()->create();
        $row = $this->fetch('dpl/listdataserver', $this->dtParams($this->viewColumns('dpl/listdata')))->json('data.0');
        foreach (['bimbingan_total', 'bimbingan_belum_lokasi', 'nama_desa', 'nama_kecamatan'] as $key) {
            $this->assertArrayNotHasKey($key, $row);
        }
    }

    public function test_lokasi_status_filter_matches_displayed_columns(): void
    {
        $this->loginAs('admin');
        $y = (int) date('Y');
        $sudah = Mahasiswa::factory()->create();
        $this->lokasi($sudah, $this->desa('Desa Set', 'Kec Set'), $y - 2);
        $nullDesa = Mahasiswa::factory()->create();
        $this->lokasi($nullDesa, null, $y);
        $none = Mahasiswa::factory()->create();

        $emails = function (array $extra) {
            $rows = collect($this->mahasiswaRows($extra)->json('data'));

            return [$rows->pluck('email')->sort()->values()->all(), $rows];
        };
        $sorted = fn (array $list) => collect($list)->sort()->values()->all();

        [$got, $rows] = $emails(['lokasi_status' => 'sudah']);
        $this->assertSame([$sudah->email], $got);
        $this->assertTrue($rows->every(fn ($r) => $r['nama_desa'] !== null));

        [$got, $rows] = $emails(['lokasi_status' => 'belum']);
        $this->assertSame($sorted([$nullDesa->email, $none->email]), $got);
        $this->assertTrue($rows->every(fn ($r) => $r['nama_desa'] === null && $r['nama_kecamatan'] === null));

        // Nilai tak dikenal / manipulasi diabaikan (semua baris), tanpa error
        foreach (['', 'semua', "belum' OR 1=1 -- ", ['belum']] as $value) {
            [$got] = $emails(['lokasi_status' => $value]);
            $this->assertCount(3, $got, 'lokasi_status='.json_encode($value));
        }
    }

    // Fixture: 2 mahasiswa PT sendiri (sudah/belum) + 1 mahasiswa PT lain (sudah)
    private function ptFixture(): array
    {
        $y = (int) date('Y');
        $pt = Satuanpendidikan::factory()->create();
        $otherPt = Satuanpendidikan::factory()->create();

        $sudah = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'nama' => 'Ani']);
        $this->lokasi($sudah, $this->desa('Desa Ptlama', 'Kec Ptlama'), $y - 1);
        $this->lokasi($sudah, $this->desa('Desa Ptbaru', 'Kec Ptbaru'), $y);
        $belum = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'nama' => 'Budi']);
        $this->lokasi($belum, null, $y);
        $asing = Mahasiswa::factory()->create(['kodept' => $otherPt->npsn, 'nama' => 'Citra']);
        $this->lokasi($asing, $this->desa('Desa Ptasing', 'Kec Ptasing'), $y);

        return [$pt, $sudah, $belum, $asing];
    }

    private function ptRows(array $extra = [], array $columnSearch = []): TestResponse
    {
        $columns = $this->viewColumns('ptmahasiswa/listdata');
        foreach ($columnSearch as $data => $value) {
            $columns[$this->columnIndex($columns, $data)]['search'] = $value;
        }

        return $this->fetch('ptmahasiswa/listdataserver', $this->dtParams($columns, $extra));
    }

    public function test_pt_listing_shows_latest_lokasi_and_filters_without_leaking(): void
    {
        [$pt, $sudah, $belum, $asing] = $this->ptFixture();
        $this->loginAs('pt', ['email' => $pt->npsn]);
        $emails = fn (TestResponse $r) => collect($r->json('data'))->pluck('email')->sort()->values()->all();
        $own = collect([$sudah->email, $belum->email])->sort()->values()->all();

        $rows = collect($this->ptRows()->json('data'))->keyBy('email');
        $this->assertSame($own, $rows->keys()->sort()->values()->all(), 'PT hanya melihat mahasiswanya');
        $this->assertSame(['Kec Ptbaru', 'Desa Ptbaru'], [$rows[$sudah->email]['nama_kecamatan'], $rows[$sudah->email]['nama_desa']]);
        $this->assertSame([null, null], [$rows[$belum->email]['nama_kecamatan'], $rows[$belum->email]['nama_desa']]);

        $this->assertSame([$sudah->email], $emails($this->ptRows(['lokasi_status' => 'sudah'])));
        $this->assertSame([$belum->email], $emails($this->ptRows(['lokasi_status' => 'belum'])));
        foreach (['', 'semua', ['sudah'], "sudah' OR 1=1 -- "] as $value) {
            $this->assertSame($own, $emails($this->ptRows(['lokasi_status' => $value])), 'lokasi_status='.json_encode($value));
        }

        // Search lokasi hanya cocok dengan lokasi terbaru, tetap dalam scope PT
        $this->assertSame([$sudah->email], $emails($this->ptRows([], ['nama_desa' => 'Ptbaru'])));
        $this->assertSame([], $emails($this->ptRows([], ['nama_desa' => 'Ptlama'])));
        $this->assertSame([], $emails($this->ptRows([], ['nama_kecamatan' => 'Ptasing'])), 'search tidak membocorkan PT lain');
        $this->assertSame([$sudah->email], $emails($this->ptRows(['search' => ['value' => 'Ptbaru', 'regex' => 'false']])));
        $this->assertSame([], $emails($this->ptRows(['search' => ['value' => 'Ptasing', 'regex' => 'false']])));
        $this->assertSame([], $emails($this->ptRows(['lokasi_status' => 'sudah', 'search' => ['value' => 'Ptasing', 'regex' => 'false']])));
    }

    public function test_pt_listing_sorts_lokasi_columns_for_kepala_and_pemda(): void
    {
        [, $sudah, $belum, $asing] = $this->ptFixture();
        $all = collect([$sudah->email, $belum->email, $asing->email])->sort()->values()->all();

        foreach (['kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $columns = $this->viewColumns('ptmahasiswa/listdata');
            $this->assertSame($all, collect($this->ptRows()->json('data'))->pluck('email')->sort()->values()->all(), "{$role} melihat semua");
            $this->assertSame([$belum->email], collect($this->ptRows(['lokasi_status' => 'belum'])->json('data'))->pluck('email')->all(), "{$role} belum");

            foreach (['nama_desa' => ['Desa Ptasing', 'Desa Ptbaru']] as $data => $asc) {
                $index = $this->columnIndex($columns, $data);
                $params = fn (string $dir) => $this->dtParams($columns, ['lokasi_status' => 'sudah', 'order' => [['column' => $index, 'dir' => $dir]]]);
                $this->assertSame($asc, $this->fetch('ptmahasiswa/listdataserver', $params('asc'))->json('data.*.'.$data), "{$role} {$data} asc");
                $this->assertSame(array_reverse($asc), $this->fetch('ptmahasiswa/listdataserver', $params('desc'))->json('data.*.'.$data), "{$role} {$data} desc");
            }
            // Status Set Lokasi (nama_kecamatan) sengaja tidak orderable; order manual tetap tanpa error
            $index = $this->columnIndex($columns, 'nama_kecamatan');
            $this->assertFalse($columns[$index]['orderable'], "{$role} nama_kecamatan tidak orderable");
            foreach (['asc', 'desc'] as $dir) {
                $this->assertCount(2, $this->fetch('ptmahasiswa/listdataserver', $this->dtParams($columns, ['lokasi_status' => 'sudah', 'order' => [['column' => $index, 'dir' => $dir]]]))->json('data'), "{$role} {$dir}");
            }
            auth()->logout();
        }
    }

    public function test_pt_listing_escapes_lokasi_names(): void
    {
        $pt = Satuanpendidikan::factory()->create();
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn]);
        $this->lokasi($mhs, $this->desa(self::XSS, '<script>alert(1)</script>'), (int) date('Y'));
        $this->loginAs('pt', ['email' => $pt->npsn]);

        $response = $this->ptRows();
        $this->assertSame(e(self::XSS), $response->json('data.0.nama_desa'));
        $this->assertSame(e('<script>alert(1)</script>'), $response->json('data.0.nama_kecamatan'));
        $this->assertStringNotContainsString('<img', $response->getContent());
        $this->assertStringNotContainsString('<script', $response->getContent());
    }

    private function mentoringRows(array $extra = []): TestResponse
    {
        $columns = array_map(fn ($d) => ['data' => $d], ['DT_RowIndex', 'id_mentoring', 'nim', 'nama', 'nm_lemb', 'prodi', 'nama_kecamatan', 'nama_desa', 'action', 'rekapnilai', 'tugasakhir']);

        return $this->fetch('dplmentoring/listdataserver', $this->dtParams($columns, $extra));
    }

    public function test_mentoring_listing_shows_latest_lokasi_and_filters_own_mentees_only(): void
    {
        $y = (int) date('Y');
        $dpl = $this->loginAs('dpl');
        $mentor = fn (string $email, ?string $dplEmail = null) => Dplmentoring::create(['email_mahasiswa' => $email, 'email_dpl' => $dplEmail ?? $dpl->email]);

        $sudah = Mahasiswa::factory()->create();
        $this->lokasi($sudah, $this->desa('Desa Mlama', 'Kec Mlama'), $y - 1);
        $this->lokasi($sudah, $this->desa('Desa Mbaru', 'Kec Mbaru'), $y);
        $nullDesa = Mahasiswa::factory()->create();
        $this->lokasi($nullDesa, null, $y);
        $none = Mahasiswa::factory()->create();
        foreach ([$sudah, $nullDesa, $none] as $m) {
            $mentor($m->email);
        }
        $asing = Mahasiswa::factory()->create();
        $this->lokasi($asing, $this->desa('Desa Masing', 'Kec Masing'), $y);
        $mentor($asing->email, 'dpl.lain@pps.test');

        $byEmail = fn (TestResponse $r) => collect($r->json('data'))->keyBy('email_mahasiswa');
        $sorted = fn (array $list) => collect($list)->sort()->values()->all();

        $rows = $byEmail($this->mentoringRows());
        $this->assertSame($sorted([$sudah->email, $nullDesa->email, $none->email]), $rows->keys()->sort()->values()->all(), 'DPL hanya melihat bimbingannya');
        $this->assertSame(['Kec Mbaru', 'Desa Mbaru'], [$rows[$sudah->email]['nama_kecamatan'], $rows[$sudah->email]['nama_desa']]);
        foreach ([$nullDesa->email, $none->email] as $email) {
            $this->assertArrayHasKey('nama_desa', $rows[$email]);
            $this->assertSame([null, null], [$rows[$email]['nama_kecamatan'], $rows[$email]['nama_desa']], $email);
        }

        $this->assertSame([$sudah->email], $byEmail($this->mentoringRows(['lokasi_status' => 'sudah']))->keys()->all());
        $this->assertSame($sorted([$nullDesa->email, $none->email]), $byEmail($this->mentoringRows(['lokasi_status' => 'belum']))->keys()->sort()->values()->all());
        foreach (['', 'semua', ['belum'], "belum' OR 1=1 -- "] as $value) {
            $this->assertCount(3, $this->mentoringRows(['lokasi_status' => $value])->json('data'), 'lokasi_status='.json_encode($value));
        }
        $this->assertStringNotContainsString($asing->email, $this->mentoringRows(['lokasi_status' => 'sudah'])->getContent());
    }

    public function test_mentoring_listing_escapes_lokasi_names(): void
    {
        $dpl = $this->loginAs('dpl');
        $mhs = Mahasiswa::factory()->create();
        $this->lokasi($mhs, $this->desa(self::XSS, '<script>alert(1)</script>'), (int) date('Y'));
        Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);

        $response = $this->mentoringRows();
        $this->assertSame(e(self::XSS), $response->json('data.0.nama_desa'));
        $this->assertSame(e('<script>alert(1)</script>'), $response->json('data.0.nama_kecamatan'));
        $this->assertStringNotContainsString('<img', $response->getContent());
        $this->assertStringNotContainsString('<script', $response->getContent());
    }

    public function test_pt_and_mentoring_views_have_lokasi_headers_and_status_filter(): void
    {
        $filterOk = function (\DOMXPath $xpath, string $view) {
            $select = $xpath->query('//select[@data-table-filter="lokasi_status"]');
            $this->assertSame(1, $select->length, "{$view}: dropdown lokasi_status");
            $target = $select->item(0)->getAttribute('data-table-target');
            $this->assertSame(1, $xpath->query('//table[@id="'.ltrim($target, '#').'"]')->length, "{$view}: target {$target} ada");
            $values = array_map(fn ($o) => $o->getAttribute('value'), iterator_to_array($xpath->query('./option', $select->item(0))));
            $this->assertSame(['', 'belum', 'sudah'], $values, "{$view}: opsi filter");
        };

        $this->loginAs('admin');
        $filterOk($this->xpathOf($this->get('mahasiswa/listdata')->assertOk()->getContent()), 'mahasiswa/listdata');

        $pt = Satuanpendidikan::factory()->create();
        $this->loginAs('pt', ['email' => $pt->npsn]);
        $xpath = $this->xpathOf($this->get('ptmahasiswa/listdata')->assertOk()->getContent());
        $filterOk($xpath, 'mahasiswa/pt/listdata');
        $table = $this->findTable($xpath, '//table[@data-datatable]');
        $columns = json_decode($table->getAttribute('data-columns'), true);
        $ths = array_map(fn ($th) => trim($th->textContent), iterator_to_array($xpath->query('./thead/tr/th', $table)));
        $this->assertCount(count($columns), $ths, 'PT: jumlah th == columns');
        $this->assertSame('Status Set Lokasi', $ths[$this->columnIndex($columns, 'nama_kecamatan')]);
        $this->assertSame('Kelurahan/Desa', $ths[$this->columnIndex($columns, 'nama_desa')]);
        $this->assertSame('Aksi', end($ths), 'PT punya kolom Aksi terakhir');
        $this->assertSame('action', end($columns)['data']);
        $this->assertFalse($table->hasAttribute('data-check'));
        $this->assertSame('ketua_kelompok', $columns[7]['data']);
        $this->assertSame(['Ketua kelompok', 'Bukan ketua'], $columns[7]['boolIcon']);
        $this->assertSame('Status Ketua Kelompok', $ths[7]);

        foreach (['kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $xpath = $this->xpathOf($this->get('ptmahasiswa/listdata')->assertOk()->getContent());
            $table = $this->findTable($xpath, '//table[@data-datatable]');
            $columns = json_decode($table->getAttribute('data-columns'), true);
            $ths = array_map(fn ($th) => trim($th->textContent), iterator_to_array($xpath->query('./thead/tr/th', $table)));
            $this->assertCount(count($columns), $ths, "{$role}: jumlah th == columns");
            $this->assertNotContains('Aksi', $ths, "{$role} tidak punya kolom Aksi");
            $this->assertNotContains('action', array_column($columns, 'data'), $role);
            $this->assertSame('ketua_kelompok', $columns[7]['data'], $role);
            $this->assertSame(['Ketua kelompok', 'Bukan ketua'], $columns[7]['boolIcon'], $role);
        }

        $this->loginAs('dpl');
        $xpath = $this->xpathOf($this->get('dplmentoring/listdata')->assertOk()->getContent());
        $filterOk($xpath, 'mentoring/listdata');
        $ths = array_map(fn ($th) => trim($th->textContent), iterator_to_array($xpath->query('//table[@id="dataTable"]/thead/tr/th')));
        $this->assertContains('Kecamatan', $ths);
        $this->assertContains('Kelurahan/Desa', $ths);
    }

    public function test_listing_routes_are_admin_only(): void
    {
        $urls = ['mahasiswa/listdata', 'mahasiswa/listdataserver?draw=1', 'dpl/listdata', 'dpl/listdataserver?draw=1'];

        foreach ($urls as $url) {
            $this->get($url, self::AJAX)->assertRedirect(route('login'));
            $this->getJson($url, self::AJAX)->assertUnauthorized();
        }

        foreach (['mahasiswa', 'dpl', 'pt', 'kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            foreach ($urls as $url) {
                $response = $this->getJson($url, self::AJAX);
                $this->assertTrue($response->isRedirect(), "{$role} {$url} harus ditolak, dapat {$response->status()}");
                $this->assertStringNotContainsString('nama_desa', $response->getContent());
            }
            auth()->logout();
        }
    }
}
