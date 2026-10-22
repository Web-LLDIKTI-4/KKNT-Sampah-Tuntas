<?php

namespace Tests\Feature\Auth;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Support\XlsxStreamer;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ReadsXlsx;
use Tests\TestCase;

class DataPesertaExportTest extends TestCase
{
    use ReadsXlsx;
    use RefreshDatabase;

    private const FILENAME = '/filename="?data_peserta_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx"?/';

    private const HEADER = ['No.', 'Kode PT', 'Nama PTS', 'Mahasiswa', 'DPL', 'PIC', 'Ketua Kelompok'];

    private Satuanpendidikan $pt;

    private Satuanpendidikan $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pt = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alfa']);
        $this->other = Satuanpendidikan::factory()->create(['nm_lemb' => '=HYPERLINK("http://x","Beta")']);

        Mahasiswa::factory()->count(2)->create(['kodept' => $this->pt->npsn]);
        $ketua = Mahasiswa::factory()->create(['kodept' => $this->pt->npsn]);
        Pjdesa::create(['email' => $ketua->email]);
        Dpl::factory()->count(2)->create(['kodept' => $this->pt->npsn]);
        User::factory()->role('pt')->create(['email' => $this->pt->npsn]);

        Dpl::factory()->create(['kodept' => $this->other->npsn]);
        // Leading-zero kodept outside ref_satuanpendidikan (nama_pt falls back to kodept)
        Mahasiswa::factory()->create(['kodept' => '001234']);
    }

    private function export()
    {
        return $this->xlsxFromResponse($this->get(route('login.data-peserta.export')), self::FILENAME);
    }

    private function loginPage(): DOMXPath
    {
        $html = $this->get(route('landing'))->assertOk()->getContent();
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    public function test_guest_downloads_xlsx_with_timestamped_filename(): void
    {
        $response = $this->get(route('login.data-peserta.export'))->assertOk();

        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));
        $this->assertMatchesRegularExpression(self::FILENAME, (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_layout_title_info_and_header(): void
    {
        $ws = $this->export();

        $this->assertSame('Data Peserta', $this->cellText($ws, 'A1'));
        $this->assertStringStartsWith('Diekspor: ', $this->cellText($ws, 'A2'));
        $this->assertSame(self::HEADER, $this->headerRow($ws));
        $this->assertSame(XlsxStreamer::HEADER_ROW, 3);
    }

    public function test_data_rows_follow_table_order_and_values(): void
    {
        $ws = $this->export();
        $xpath = $this->loginPage();

        $tableRows = [];
        foreach ($xpath->query('//tbody[@id="pesertaTbody"]/tr') as $tr) {
            $cells = [];
            foreach ($xpath->query('./td', $tr) as $td) {
                $cells[] = trim($td->textContent);
            }
            $tableRows[] = $cells;
        }

        $data = $this->dataRows($ws);
        array_pop($data); // Total row
        $this->assertCount(count($tableRows), $data);

        foreach ($tableRows as $i => $cells) {
            $this->assertSame($i + 1, (int) $data[$i][0], 'No. row '.$i);
            $this->assertSame($cells[1], (string) $data[$i][1], 'Kode PT row '.$i);
            $this->assertSame($cells[2], (string) $data[$i][2], 'Nama PTS row '.$i);
            for ($c = 3; $c <= 6; $c++) {
                $this->assertSame((int) str_replace('.', '', $cells[$c]), (int) $data[$i][$c], "col {$c} row {$i}");
            }
        }

        $byKode = collect($data)->keyBy(fn ($row) => (string) $row[1]);
        $this->assertSame([2 + 1, 2, 1, 1], array_map('intval', array_slice($byKode[$this->pt->npsn], 3)));
    }

    public function test_leading_zero_kodept_kept_as_text(): void
    {
        $ws = $this->export();
        $first = XlsxStreamer::HEADER_ROW + 1;
        $kodes = [];
        for ($r = $first; $r < $ws->getHighestRow(); $r++) {
            $kodes[$r] = $this->cellText($ws, 'B'.$r);
        }

        $row = array_search('001234', $kodes, true);
        $this->assertNotFalse($row, 'kodept 001234 tidak ditemukan utuh');
        $this->assertCellIsText($ws, 'B'.$row, '001234');

        $ptRow = array_search((string) $this->pt->npsn, $kodes, true);
        $this->assertStringStartsWith('0', (string) $this->pt->npsn);
        $this->assertCellIsText($ws, 'B'.$ptRow, (string) $this->pt->npsn);
    }

    public function test_nama_pt_formula_is_written_as_text(): void
    {
        $ws = $this->export();
        $first = XlsxStreamer::HEADER_ROW + 1;
        for ($r = $first; $r < $ws->getHighestRow(); $r++) {
            if ($this->cellText($ws, 'B'.$r) === (string) $this->other->npsn) {
                $this->assertCellIsText($ws, 'C'.$r, '=HYPERLINK("http://x","Beta")');

                return;
            }
        }
        $this->fail('Baris PT other tidak ditemukan');
    }

    public function test_total_row_matches_table_tfoot(): void
    {
        $ws = $this->export();
        $tfoot = array_map(
            fn ($th) => trim($th->textContent),
            iterator_to_array($this->loginPage()->query('//table[contains(@class,"peserta-table")]/tfoot/tr/th'))
        );

        $last = $ws->getHighestRow();
        $this->assertSame('Total', $this->cellText($ws, 'A'.$last));
        $this->assertContains('A'.$last.':C'.$last, array_values($ws->getMergeCells()));
        foreach (['D', 'E', 'F', 'G'] as $i => $col) {
            $this->assertSame((int) str_replace('.', '', $tfoot[$i + 1]), (int) $ws->getCell($col.$last)->getValue(), 'Total '.$col);
        }
        $this->assertSame([4, 3, 1, 1], array_map(fn ($c) => (int) $ws->getCell($c.$last)->getValue(), ['D', 'E', 'F', 'G']));
    }

    public function test_empty_data_exports_header_and_zero_total(): void
    {
        Mahasiswa::query()->delete();
        Dpl::query()->delete();

        $ws = $this->export();

        $this->assertSame(self::HEADER, $this->headerRow($ws));
        $this->assertSame(XlsxStreamer::HEADER_ROW + 1, $ws->getHighestRow());
        $this->assertSame('Total', $this->cellText($ws, 'A4'));
        foreach (['D', 'E', 'F', 'G'] as $col) {
            $this->assertSame(0, (int) $ws->getCell($col.'4')->getValue());
        }
    }

    public function test_authenticated_user_can_download_like_laporan_export(): void
    {
        $this->actingAs(User::factory()->role('pt')->create());

        $this->get(route('login.laporan.export'))->assertOk();
        $this->assertMatchesRegularExpression(self::FILENAME, (string) $this->get(route('login.data-peserta.export'))->assertOk()->headers->get('content-disposition'));
    }

    public function test_eleventh_request_in_a_minute_is_throttled(): void
    {
        $url = route('login.data-peserta.export');
        for ($i = 0; $i < 10; $i++) {
            $this->get($url)->assertOk();
        }
        $this->get($url)->assertStatus(429);
    }

    public function test_login_page_shows_export_button(): void
    {
        $links = $this->loginPage()->query('//div[contains(@class,"peserta-toolbar")]//a[@href="'.route('login.data-peserta.export').'"]');

        $this->assertSame(1, $links->length);
        $this->assertStringContainsString('Unduh Data Excel', $links->item(0)->textContent);
    }
}
