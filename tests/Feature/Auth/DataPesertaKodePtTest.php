<?php

namespace Tests\Feature\Auth;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataPesertaKodePtTest extends TestCase
{
    use RefreshDatabase;

    private Satuanpendidikan $pt;

    private Satuanpendidikan $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pt = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alfa']);
        $this->other = Satuanpendidikan::factory()->create(['nm_lemb' => 'Politeknik Beta']);

        Mahasiswa::factory()->create(['kodept' => $this->pt->npsn]);
        Dpl::factory()->create(['kodept' => $this->other->npsn]);
        // PT absent from ref_satuanpendidikan falls back to kodept as nama_pt
        Mahasiswa::factory()->create(['kodept' => 'XK01']);
    }

    private function pesertaTable(): DOMXPath
    {
        $html = $this->get(route('landing'))->assertOk()->getContent();
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    private function rows(DOMXPath $xpath): array
    {
        $rows = [];
        foreach ($xpath->query('//tbody[@id="pesertaTbody"]/tr') as $tr) {
            $cells = [];
            foreach ($xpath->query('./td', $tr) as $td) {
                $cells[] = trim($td->textContent);
            }
            $rows[] = ['search' => $tr->getAttribute('data-search'), 'cells' => $cells];
        }

        return $rows;
    }

    public function test_header_has_kode_pt_column_before_nama_pts(): void
    {
        $headers = array_map(
            fn ($th) => trim($th->textContent),
            iterator_to_array($this->pesertaTable()->query('//table[contains(@class,"peserta-table")]/thead/tr/th'))
        );

        $this->assertSame('Kode PT', $headers[1]);
        $this->assertSame('Nama PTS', $headers[2]);
    }

    public function test_each_row_renders_its_kodept_next_to_nama_pt(): void
    {
        $byKode = collect($this->rows($this->pesertaTable()))->keyBy(fn ($row) => $row['cells'][1]);

        $this->assertEqualsCanonicalizing([(string) $this->pt->npsn, (string) $this->other->npsn, 'XK01'], $byKode->keys()->all());
        $this->assertSame('Universitas Alfa', $byKode[(string) $this->pt->npsn]['cells'][2]);
        $this->assertSame('Politeknik Beta', $byKode[(string) $this->other->npsn]['cells'][2]);
        $this->assertSame('XK01', $byKode['XK01']['cells'][2]);
    }

    public function test_data_search_contains_lowercased_kodept_and_nama_pt(): void
    {
        $byKode = collect($this->rows($this->pesertaTable()))->keyBy(fn ($row) => $row['cells'][1]);

        $this->assertSame(mb_strtolower($this->pt->npsn.' Universitas Alfa'), $byKode[(string) $this->pt->npsn]['search']);
        // Mixed-case kodept is searchable via lowercased keyword (JS lowercases input)
        $this->assertStringContainsString('xk01', $byKode['XK01']['search']);
        $this->assertStringNotContainsString(mb_strtolower($this->pt->npsn), $byKode[(string) $this->other->npsn]['search']);
    }

    public function test_header_body_and_footer_column_counts_match(): void
    {
        $xpath = $this->pesertaTable();
        $table = '//table[contains(@class,"peserta-table")]';

        $headerCount = $xpath->query($table.'/thead/tr/th')->length;
        $footerCount = 0;
        foreach ($xpath->query($table.'/tfoot/tr/th') as $th) {
            $footerCount += (int) ($th->getAttribute('colspan') ?: 1);
        }

        $this->assertSame(7, $headerCount);
        $this->assertSame('3', $xpath->query($table.'/tfoot/tr/th')->item(0)->getAttribute('colspan'));
        $this->assertSame($headerCount, $footerCount);
        foreach ($this->rows($xpath) as $row) {
            $this->assertCount($headerCount, $row['cells']);
        }
    }
}
