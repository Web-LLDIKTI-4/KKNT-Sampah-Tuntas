<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InspectsSortableTables;
use Tests\TestCase;

// Filter ketua_kelompok + kolom boolean ketua_kelompok di Data Mahasiswa admin & PT
class KetuaKelompokFilterTest extends TestCase
{
    use InspectsSortableTables;
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private const IGNORED_VALUES = ['', '2', 'abc', "1' OR 1=1 -- ", ['1'], ['0', '1']];

    private Satuanpendidikan $pt;

    /** @var array<string, Mahasiswa> */
    private array $mhs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pt = Satuanpendidikan::factory()->create();
        $otherPt = Satuanpendidikan::factory()->create();
        $y = (int) date('Y');

        // Ketua (lokasi sudah), ketua (lokasi belum), non-ketua (lokasi sudah), non-ketua tanpa lokasi, ketua PT lain
        $this->mhs['ketuaSudah'] = $this->mahasiswa($this->pt, true);
        $this->mhs['ketuaBelum'] = $this->mahasiswa($this->pt, false);
        $this->mhs['biasaSudah'] = $this->mahasiswa($this->pt, true);
        $this->mhs['biasaBelum'] = $this->mahasiswa($this->pt, false);
        $this->mhs['ketuaAsing'] = $this->mahasiswa($otherPt, true);

        foreach (['ketuaSudah', 'ketuaBelum', 'ketuaAsing'] as $key) {
            Pjdesa::create(['email' => $this->mhs[$key]->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        }
        // Baris pj_desa yang emailnya tidak cocok dengan mahasiswa mana pun tidak boleh mempengaruhi filter
        Pjdesa::create(['email' => 'orphan-'.uniqid().'@example.test', 'id_desa' => Desa::factory()->create()->id_desa]);
        Mahasiswa_lokasi::create(['tahun' => $y, 'id_mahasiswa' => $this->mhs['biasaBelum']->id_mahasiswa, 'id_desa' => null]);
    }

    private function mahasiswa(Satuanpendidikan $pt, bool $lokasi): Mahasiswa
    {
        $mahasiswa = Mahasiswa::factory()->create(['kodept' => $pt->npsn]);
        if ($lokasi) {
            Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mahasiswa->id_mahasiswa, 'id_desa' => Desa::factory()->create()->id_desa]);
        }

        return $mahasiswa;
    }

    private function rows(string $endpoint, array $params = []): TestResponse
    {
        // Kolom email dikirim agar global search DataTables punya target
        $columns = [['data' => 'email', 'name' => 'email', 'searchable' => 'true', 'orderable' => 'false', 'search' => ['value' => '', 'regex' => 'false']]];
        $response = $this->getJson($endpoint.'?'.http_build_query($params + ['draw' => 1, 'start' => 0, 'length' => 100, 'columns' => $columns]), self::AJAX)->assertOk();
        $this->assertArrayNotHasKey('error', $response->json(), (string) $response->json('error'));

        return $response;
    }

    private function emails(string $endpoint, array $params = []): array
    {
        return collect($this->rows($endpoint, $params)->json('data'))->pluck('email')->sort()->values()->all();
    }

    private function emailsOf(string ...$keys): array
    {
        return collect($keys)->map(fn ($k) => $this->mhs[$k]->email)->sort()->values()->all();
    }

    private function assertFilterSelect(string $html, string $view): void
    {
        $xpath = $this->xpathOf($html);
        $select = $xpath->query('//select[@id="filterKetuaKelompok"]');
        $this->assertSame(1, $select->length, "{$view}: select #filterKetuaKelompok");
        $select = $select->item(0);
        $this->assertSame('ketua_kelompok', $select->getAttribute('data-table-filter'), $view);
        $target = ltrim($select->getAttribute('data-table-target'), '#');
        $this->assertSame(1, $xpath->query('//table[@id="'.$target.'"][@data-datatable]')->length, "{$view}: target tabel ada");
        $values = array_map(fn ($o) => $o->getAttribute('value'), iterator_to_array($xpath->query('./option', $select)));
        $this->assertSame(['', '1', '0'], $values, "{$view}: opsi filter");
        $this->assertSame(1, $xpath->query('//select[@data-table-filter="lokasi_status"]')->length, "{$view}: filter lokasi tetap ada");
    }

    public function test_admin_filter_ketua_kelompok(): void
    {
        $this->loginAs('admin');
        $url = 'mahasiswa/listdataserver';
        $all = $this->emailsOf('ketuaSudah', 'ketuaBelum', 'biasaSudah', 'biasaBelum', 'ketuaAsing');

        $this->assertSame($all, $this->emails($url));
        $this->assertSame($this->emailsOf('ketuaSudah', 'ketuaBelum', 'ketuaAsing'), $this->emails($url, ['ketua_kelompok' => '1']));
        $this->assertSame($this->emailsOf('biasaSudah', 'biasaBelum'), $this->emails($url, ['ketua_kelompok' => '0']));

        // Kolom ketua_kelompok boolean & konsisten dengan filter
        foreach (['1' => true, '0' => false] as $value => $expected) {
            foreach ($this->rows($url, ['ketua_kelompok' => (string) $value])->json('data') as $row) {
                $this->assertSame($expected, $row['ketua_kelompok'], "ketua_kelompok={$value}");
                $this->assertArrayNotHasKey('ketua_status', $row);
            }
        }

        foreach (self::IGNORED_VALUES as $value) {
            $this->assertSame($all, $this->emails($url, ['ketua_kelompok' => $value]), 'diabaikan: '.json_encode($value));
        }
    }

    public function test_admin_filter_combines_with_lokasi_status(): void
    {
        $this->loginAs('admin');
        $url = 'mahasiswa/listdataserver';

        $this->assertSame($this->emailsOf('ketuaSudah', 'ketuaAsing'), $this->emails($url, ['ketua_kelompok' => '1', 'lokasi_status' => 'sudah']));
        $this->assertSame($this->emailsOf('ketuaBelum'), $this->emails($url, ['ketua_kelompok' => '1', 'lokasi_status' => 'belum']));
        $this->assertSame($this->emailsOf('biasaSudah'), $this->emails($url, ['ketua_kelompok' => '0', 'lokasi_status' => 'sudah']));
        $this->assertSame($this->emailsOf('biasaBelum'), $this->emails($url, ['ketua_kelompok' => '0', 'lokasi_status' => 'belum']));
        // Global search tetap di-AND dengan filter
        $this->assertSame([], $this->emails($url, ['ketua_kelompok' => '0', 'search' => ['value' => $this->mhs['ketuaSudah']->email, 'regex' => 'false']]));
    }

    public function test_pt_filter_only_own_mahasiswa(): void
    {
        $this->loginAs('pt', ['email' => $this->pt->npsn]);
        $url = 'ptmahasiswa/listdataserver';
        $own = $this->emailsOf('ketuaSudah', 'ketuaBelum', 'biasaSudah', 'biasaBelum');

        $this->assertSame($own, $this->emails($url));
        $this->assertSame($this->emailsOf('ketuaSudah', 'ketuaBelum'), $this->emails($url, ['ketua_kelompok' => '1']));
        $this->assertSame($this->emailsOf('biasaSudah', 'biasaBelum'), $this->emails($url, ['ketua_kelompok' => '0']));
        $this->assertSame($this->emailsOf('ketuaSudah'), $this->emails($url, ['ketua_kelompok' => '1', 'lokasi_status' => 'sudah']));
        $this->assertSame($this->emailsOf('biasaBelum'), $this->emails($url, ['ketua_kelompok' => '0', 'lokasi_status' => 'belum']));
        // Ketua PT lain tidak bocor walau dicari langsung
        $search = ['value' => $this->mhs['ketuaAsing']->email, 'regex' => 'false'];
        $this->assertSame([], $this->emails($url, ['ketua_kelompok' => '1', 'search' => $search]));

        foreach (['1' => true, '0' => false] as $value => $expected) {
            foreach ($this->rows($url, ['ketua_kelompok' => (string) $value])->json('data') as $row) {
                $this->assertSame($expected, $row['ketua_kelompok'], "ketua_kelompok={$value}");
            }
        }

        foreach (self::IGNORED_VALUES as $value) {
            $this->assertSame($own, $this->emails($url, ['ketua_kelompok' => $value]), 'diabaikan: '.json_encode($value));
        }
    }

    public function test_kepala_and_pemda_filter_all_pt(): void
    {
        foreach (['kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $this->assertSame($this->emailsOf('ketuaSudah', 'ketuaBelum', 'ketuaAsing'), $this->emails('ptmahasiswa/listdataserver', ['ketua_kelompok' => '1']), $role);
            $this->assertSame($this->emailsOf('biasaSudah', 'biasaBelum'), $this->emails('ptmahasiswa/listdataserver', ['ketua_kelompok' => '0']), $role);
            auth()->logout();
        }
    }

    public function test_views_render_ketua_kelompok_filter(): void
    {
        $this->loginAs('admin');
        $this->assertFilterSelect($this->get('mahasiswa/listdata')->assertOk()->getContent(), 'admin');
        auth()->logout();

        $this->loginAs('pt', ['email' => $this->pt->npsn]);
        $this->assertFilterSelect($this->get('ptmahasiswa/listdata')->assertOk()->getContent(), 'pt');
        auth()->logout();

        foreach (['kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $this->assertFilterSelect($this->get('ptmahasiswa/listdata')->assertOk()->getContent(), $role);
            auth()->logout();
        }
    }

    public function test_filter_endpoints_reject_unauthorized(): void
    {
        $params = '?draw=1&ketua_kelompok=1';
        foreach (['mahasiswa/listdataserver', 'ptmahasiswa/listdataserver'] as $url) {
            $this->getJson($url.$params, self::AJAX)->assertUnauthorized();
        }

        foreach (['mahasiswa', 'dpl'] as $role) {
            $this->loginAs($role);
            foreach (['mahasiswa/listdataserver', 'ptmahasiswa/listdataserver'] as $url) {
                $response = $this->getJson($url.$params, self::AJAX);
                $this->assertFalse($response->isOk(), "{$role} {$url} harus ditolak");
                $this->assertStringNotContainsString($this->mhs['ketuaSudah']->email, $response->getContent());
            }
            auth()->logout();
        }

        // PT tidak boleh mengakses listing admin
        $this->loginAs('pt', ['email' => $this->pt->npsn]);
        $response = $this->getJson('mahasiswa/listdataserver'.$params, self::AJAX);
        $this->assertFalse($response->isOk());
        $this->assertStringNotContainsString($this->mhs['ketuaAsing']->email, $response->getContent());
    }
}
