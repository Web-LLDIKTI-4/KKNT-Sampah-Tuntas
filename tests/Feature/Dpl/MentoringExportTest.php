<?php

namespace Tests\Feature\Dpl;

use App\Http\Requests\Dpl\PenilaianLogbulananRequest;
use App\Models\Desa;
use App\Models\Dplmentoring;
use App\Models\Kecamatan;
use App\Models\Logbulanan;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Satuanpendidikan;
use App\Models\Tugasakhir;
use App\Models\User;
use App\Support\XlsxStreamer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\Concerns\ReadsXlsx;
use Tests\TestCase;

class MentoringExportTest extends TestCase
{
    use ReadsXlsx;
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    // Same order as columns in mentoring/listdata.blade.php
    private const COLUMNS = ['DT_RowIndex', 'id_mentoring', 'nim', 'nama', 'nm_lemb', 'prodi', 'nama_kecamatan', 'nama_desa', 'action', 'rekapnilai', 'tugasakhir'];

    private const HEADER = ['No.', 'NIM', 'Nama', 'Perguruan Tinggi', 'Prodi', 'Kecamatan', 'Kelurahan/Desa', 'Rata-rata Nilai Log Bulanan', 'Tugas Akhir'];

    // listdataserver JSON row keys (sorted), same as before the export columns were added
    private const LIST_KEYS = [
        'DT_RowIndex', 'action', 'created_at', 'email_dpl', 'email_mahasiswa', 'id_mentoring', 'mahasiswa', 'nama', 'nama_desa',
        'nama_kecamatan', 'nilai_freeform', 'nim', 'nm_lemb', 'prodi', 'rekapnilai', 'tugasakhir', 'updated_at',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function params(array $extra = []): array
    {
        $params = [];
        foreach (self::COLUMNS as $i => $data) {
            $params['columns'][$i] = ['data' => $data, 'name' => $data, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']];
        }

        return array_replace_recursive($params, $extra);
    }

    private function exportUri(array $extra = []): string
    {
        return 'dplmentoring/export?'.http_build_query($this->params($extra));
    }

    private function sheet(string $uri): Worksheet
    {
        return $this->xlsxFromResponse($this->get($uri), '/filename=data_mahasiswa_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx/');
    }

    private function mentee(User $dpl, array $attrs = [], ?string $desa = null, ?string $kecamatan = null): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create($attrs);
        Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);
        if ($desa !== null) {
            Mahasiswa_lokasi::create([
                'tahun' => (int) date('Y'),
                'id_mahasiswa' => $mhs->id_mahasiswa,
                'id_desa' => Desa::factory()->create([
                    'desa' => $desa,
                    'id_kecamatan' => Kecamatan::factory()->create(['kecamatan' => $kecamatan])->id_kecamatan,
                ])->id_desa,
            ]);
        }

        return $mhs;
    }

    public function test_dpl_downloads_all_mentees_with_header_ignoring_paging(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $dpl = $this->loginAs('dpl');
        $pt = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alfa']);
        $this->mentee($dpl, ['nim' => '100000001', 'nama' => 'Ani', 'prodi' => 'Manajemen', 'kodept' => $pt->npsn], 'Desa Satu', 'Kec Satu');
        foreach (range(1, 6) as $i) {
            $this->mentee($dpl);
        }

        $ws = $this->sheet($this->exportUri(['draw' => 1, 'start' => 0, 'length' => 2]));

        $this->assertSame(self::HEADER, $this->headerRow($ws));
        $this->assertCount(7, $this->dataRows($ws));
        $this->assertSame(range(1, 7), array_map('intval', $this->dataColumn($ws, 0)));
        $ani = collect($this->dataRows($ws))->keyBy(1)['100000001'];
        $this->assertSame(['Ani', 'Universitas Alfa', 'Manajemen', 'Kec Satu', 'Desa Satu', '-', '-'], array_slice($ani, 2));
    }

    public function test_export_only_contains_own_mentees(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $dplB = User::factory()->role('dpl')->create();
        $this->mentee($dplB, ['nim' => '200000002']);
        $dplA = $this->loginAs('dpl');
        $this->mentee($dplA, ['nim' => '100000001']);

        $ws = $this->sheet($this->exportUri());
        $this->assertSame(['100000001'], array_map('strval', $this->dataColumn($ws, 1)));
    }

    public function test_export_respects_lokasi_status_search_and_order(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $dpl = $this->loginAs('dpl');
        $this->mentee($dpl, ['nim' => '100000001', 'nama' => 'Budi Sudah'], 'Desa Ada', 'Kec Ada');
        $this->mentee($dpl, ['nim' => '100000002', 'nama' => 'Cici Belum']);
        $this->mentee($dpl, ['nim' => '100000003', 'nama' => 'Ani Belum']);
        $nim = fn (Worksheet $ws) => array_map('strval', $this->dataColumn($ws, 1));

        $this->assertSame(['100000001'], $nim($this->sheet($this->exportUri(['lokasi_status' => 'sudah']))));
        $ws = $this->sheet($this->exportUri(['lokasi_status' => 'belum', 'order' => [['column' => 3, 'dir' => 'asc']]]));
        $this->assertSame(['100000003', '100000002'], $nim($ws));
        $this->assertStringContainsString('Belum set', $this->cellText($ws, 'A2'));
        // Unknown status is ignored
        $this->assertCount(3, $nim($this->sheet($this->exportUri(['lokasi_status' => 'x']))));

        $ws = $this->sheet($this->exportUri(['search' => ['value' => 'Cici', 'regex' => 'false']]));
        $this->assertSame(['100000002'], $nim($ws));
        $this->assertStringContainsString('Cici', $this->cellText($ws, 'A2'));

        $this->assertSame(['100000003', '100000002', '100000001'], $nim($this->sheet($this->exportUri(['order' => [['column' => 2, 'dir' => 'desc']]]))));
        $this->assertSame(['100000003', '100000001', '100000002'], $nim($this->sheet($this->exportUri(['order' => [['column' => 3, 'dir' => 'asc']]]))));
        // Kecamatan order (subquery)
        $this->assertSame('100000001', $nim($this->sheet($this->exportUri(['order' => [['column' => 6, 'dir' => 'desc']]])))[0]);

        // HTML columns: order/search ignored, never error
        foreach ([8, 9, 10] as $col) {
            $this->assertCount(3, $nim($this->sheet($this->exportUri(['order' => [['column' => $col, 'dir' => 'desc']]]))), "order $col");
            $this->assertCount(3, $nim($this->sheet($this->exportUri(['columns' => [$col => ['search' => ['value' => 'zzz']]]]))), "search $col");
        }
        $this->sheet($this->exportUri(['order' => [['column' => 99, 'dir' => 'desc;drop']]]));
    }

    public function test_export_formula_values_stay_text(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $dpl = $this->loginAs('dpl');
        $this->mentee($dpl, ['nama' => '=HYPERLINK("http://evil.test","x")']);

        $ws = $this->sheet('dplmentoring/export');
        $this->assertCellIsText($ws, 'C'.(XlsxStreamer::HEADER_ROW + 1), '=HYPERLINK("http://evil.test","x")');

        $ws = $this->sheet('dplmentoring/export?search[value]='.urlencode('=1+1'));
        $this->assertCellIsText($ws, 'A2', $this->cellText($ws, 'A2'));
        $this->assertStringContainsString('=1+1', $this->cellText($ws, 'A2'));
    }

    public function test_duplicate_mahasiswa_email_picks_latest_id(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $dpl = $this->loginAs('dpl');
        $old = Mahasiswa::factory()->create(['nim' => '100000001', 'nama' => 'Lama']);
        $new = Mahasiswa::factory()->create(['nim' => '100000002', 'nama' => 'Baru', 'email' => $old->email]);
        $this->assertGreaterThan($old->id_mahasiswa, $new->id_mahasiswa);
        Dplmentoring::create(['email_mahasiswa' => $old->email, 'email_dpl' => $dpl->email]);

        foreach (['asc', 'desc'] as $dir) {
            $ws = $this->sheet($this->exportUri(['order' => [['column' => 2, 'dir' => $dir]]]));
            $this->assertSame([['100000002', 'Baru']], array_map(fn ($r) => [(string) $r[1], $r[2]], $this->dataRows($ws)), $dir);
        }
        $this->assertSame('Baru', $this->getJson('dplmentoring/listdataserver?'.http_build_query($this->params()), self::AJAX)->json('data.0.nama'));
    }

    public function test_export_forbidden_for_other_roles_and_guest(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->get('dplmentoring/export')->assertRedirect(route('login'));

        foreach (['pt', 'kepala', 'pemda', 'admin', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            $status = $this->get('dplmentoring/export')->status();
            $this->assertContains($status, [302, 403], "$role export: $status");
        }
    }

    public function test_export_is_throttled(): void
    {
        $this->loginAs('dpl');
        foreach (range(1, 3) as $i) {
            $this->get('dplmentoring/export')->assertOk();
        }
        $this->get('dplmentoring/export')->assertStatus(429);
    }

    public function test_listdataserver_keeps_scope_for_viewer_roles(): void
    {
        foreach (['dpl', 'pt', 'kepala', 'pemda'] as $role) {
            $other = User::factory()->role('dpl')->create();
            $this->mentee($other, ['nim' => '9'.random_int(10000000, 99999999)]);
            $user = $this->loginAs($role);
            $own = $this->mentee($user);

            $res = $this->getJson('dplmentoring/listdataserver?'.http_build_query($this->params(['draw' => 1, 'start' => 0, 'length' => 10])), self::AJAX)->assertOk();
            $this->assertSame(1, $res->json('recordsTotal'), $role);
            $this->assertSame([(string) $own->nim], array_map('strval', array_column($res->json('data'), 'nim')), $role);
        }
    }

    public function test_listdata_shows_export_button_for_dpl_only(): void
    {
        $this->loginAs('dpl');
        $this->get('dplmentoring/listdata')->assertOk()
            ->assertSee('href="'.route('dplmentoring.export').'"', false)
            ->assertSee('data-export-table="#dataTable"', false);
        $this->assertSame(url('dplmentoring/export'), route('dplmentoring.export'));

        foreach (['pt', 'kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $this->get('dplmentoring/listdata')->assertOk()
                ->assertDontSee(route('dplmentoring.export'), false);
        }
    }

    private function exportRow(Worksheet $ws, string $nim): int
    {
        $index = array_search($nim, array_map('strval', $this->dataColumn($ws, 1)), true);
        $this->assertNotFalse($index, "nim $nim");

        return XlsxStreamer::HEADER_ROW + 1 + $index;
    }

    private function tugasakhir(Mahasiswa $mhs, string $tautan, ?string $createdAt = null, ?string $id = null): Tugasakhir
    {
        $ta = new Tugasakhir(['email' => $mhs->email, 'tautan' => $tautan, 'tahun' => (int) date('Y')]);
        if ($id !== null) {
            $ta->id_tugasakhir = $id;
        }
        if ($createdAt !== null) {
            $ta->created_at = $createdAt;
        }
        $ta->save();

        return $ta;
    }

    public function test_export_average_counts_only_valid_grades_of_own_logs(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $dpl = $this->loginAs('dpl');
        $ani = $this->mentee($dpl, ['nim' => '100000001']);
        $budi = $this->mentee($dpl, ['nim' => '100000002']);
        $cici = $this->mentee($dpl, ['nim' => '100000003']);
        $other = $this->mentee(User::factory()->role('dpl')->create(), ['nim' => '200000001']);

        $this->assertContains('10', PenilaianLogbulananRequest::PILIHAN_NILAI);
        $this->assertContains('20', PenilaianLogbulananRequest::PILIHAN_NILAI);
        $this->assertContains('100', PenilaianLogbulananRequest::PILIHAN_NILAI);
        // (10 + 20 + 20) / 3 = 16.666… -> 16.67; empty, null, non-numeric & off-list values ignored
        foreach (['10', '20', '20', '', null, 'abc', '5', '0', ' 80', '80a', '100.5'] as $nilai) {
            Logbulanan::factory()->create(['email' => $ani->email, 'nilai' => $nilai]);
        }
        // Grades of others never leak in
        Logbulanan::factory()->create(['email' => $other->email, 'nilai' => '100']);
        Logbulanan::factory()->create(['email' => 'nobody@pps.test', 'nilai' => '100']);
        // Only ungraded / invalid -> "-"
        Logbulanan::factory()->create(['email' => $budi->email, 'nilai' => null]);
        Logbulanan::factory()->create(['email' => $budi->email, 'nilai' => 'abc']);
        // Whole number stays whole
        foreach (['90', '100', '80'] as $nilai) {
            Logbulanan::factory()->create(['email' => $cici->email, 'nilai' => $nilai]);
        }

        $ws = $this->sheet($this->exportUri());
        $this->assertCount(3, $this->dataRows($ws));

        $cell = $ws->getCell('H'.$this->exportRow($ws, '100000001'));
        $this->assertSame(16.67, (float) $cell->getValue());
        $this->assertNotSame('s', $cell->getDataType());
        $this->assertSame('-', $this->cellText($ws, 'H'.$this->exportRow($ws, '100000002')));
        $this->assertEquals(90, $ws->getCell('H'.$this->exportRow($ws, '100000003'))->getValue());
    }

    public function test_export_tugas_akhir_uses_latest_safe_url(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $dpl = $this->loginAs('dpl');
        $latest = $this->mentee($dpl, ['nim' => '100000001']);
        $tie = $this->mentee($dpl, ['nim' => '100000002']);
        $js = $this->mentee($dpl, ['nim' => '100000003']);
        $ftp = $this->mentee($dpl, ['nim' => '100000004']);
        $this->mentee($dpl, ['nim' => '100000005']);
        $formula = $this->mentee($dpl, ['nim' => '100000006']);
        $withEq = $this->mentee($dpl, ['nim' => '100000007']);

        $this->tugasakhir($latest, 'https://new.test/b', now()->subMinute()->toDateTimeString());
        $this->tugasakhir($latest, 'https://old.test/a', now()->subDays(2)->toDateTimeString());
        // Same created_at -> highest id wins (inserted high first so insert order can't decide)
        $same = now()->subHour()->toDateTimeString();
        $this->tugasakhir($tie, 'https://high.test', $same, 'ffffffff-ffff-ffff-ffff-ffffffffffff');
        $this->tugasakhir($tie, 'https://low.test', $same, '00000000-0000-0000-0000-000000000001');
        $this->tugasakhir($js, 'javascript:alert(1)');
        $this->tugasakhir($ftp, 'ftp://files.test/x');
        $this->tugasakhir($formula, '=HYPERLINK("http://evil.test","x")');
        $url = 'https://drive.test/view?id=abc&x==1+1';
        $this->tugasakhir($withEq, $url);

        $ws = $this->sheet($this->exportUri());
        $ta = fn (string $nim) => $this->cellText($ws, 'I'.$this->exportRow($ws, $nim));

        $this->assertSame('https://new.test/b', $ta('100000001'));
        $this->assertSame('https://high.test', $ta('100000002'));
        $this->assertSame('-', $ta('100000003'));
        $this->assertSame('-', $ta('100000004'));
        $this->assertSame('-', $ta('100000005'));
        $this->assertSame('-', $ta('100000006'));
        $this->assertCellIsText($ws, 'I'.$this->exportRow($ws, '100000007'), $url);
        $this->assertCount(7, $this->dataRows($ws));
    }

    public function test_listdataserver_columns_unchanged_and_show_latest_tugas_akhir(): void
    {
        foreach (['dpl', 'pt', 'kepala', 'pemda'] as $role) {
            $user = $this->loginAs($role);
            $mhs = $this->mentee($user);
            Logbulanan::factory()->create(['email' => $mhs->email, 'nilai' => '80']);
            $this->tugasakhir($mhs, 'https://old-'.$role.'.test', now()->subDays(2)->toDateTimeString());
            $this->tugasakhir($mhs, 'https://new-'.$role.'.test', now()->subMinute()->toDateTimeString());

            $row = $this->getJson('dplmentoring/listdataserver?'.http_build_query($this->params(['draw' => 1, 'start' => 0, 'length' => 10])), self::AJAX)
                ->assertOk()->json('data.0');

            $keys = array_keys($row);
            sort($keys);
            $this->assertSame(self::LIST_KEYS, $keys, $role);
            $this->assertIsString($row['tugasakhir'], $role);
            $this->assertStringContainsString('href="https://new-'.$role.'.test"', $row['tugasakhir'], $role);
            $this->assertStringNotContainsString('old-'.$role, $row['tugasakhir'], $role);
        }
    }
}
